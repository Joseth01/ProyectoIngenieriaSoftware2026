"""
BovWeight – Microservicio de estimación de peso bovino
Flask + YOLOv8n ONNX (sin PyTorch en runtime, ~100 MB RAM)

POST /detectar
  Body (multipart/form-data):
    imagen  : archivo de imagen del bovino
    raza    : (opcional) brahman | holstein | angus | nelore
    edad    : (opcional) edad en meses

Response 200:
  { "peso_estimado": 385.4, "confianza": 88.2,
    "alzada_estimada": 138.5, "condicion_corporal": 3.2, "mensaje": "..." }

Response 422:
  { "error": "...", "codigo": "NO_BOVINO" | "MULTIPLES_ANIMALES" | "COBERTURA_INSUFICIENTE" }
"""

import io
import os

import numpy as np
import onnxruntime as ort
from flask import Flask, request, jsonify
from PIL import Image

app = Flask(__name__)

MODEL_PATH    = os.environ.get("MODEL_PATH", "yolov8n.onnx")
COCO_COW_CLS  = 19
MIN_CONF      = 0.40
INPUT_SIZE    = 640

_session = None


def get_session() -> ort.InferenceSession:
    global _session
    if _session is None:
        _session = ort.InferenceSession(
            MODEL_PATH,
            providers=["CPUExecutionProvider"]
        )
    return _session


def preprocess(img: Image.Image) -> np.ndarray:
    """Redimensiona y normaliza la imagen para YOLOv8."""
    img_rgb    = img.convert("RGB").resize((INPUT_SIZE, INPUT_SIZE))
    arr        = np.array(img_rgb, dtype=np.float32) / 255.0   # (H,W,3)
    arr        = arr.transpose(2, 0, 1)                         # (3,H,W)
    return arr[np.newaxis, ...]                                  # (1,3,H,W)


def iou(a: dict, b: dict) -> float:
    """Intersection over Union entre dos bounding boxes."""
    ix1 = max(a["x1"], b["x1"])
    iy1 = max(a["y1"], b["y1"])
    ix2 = min(a["x2"], b["x2"])
    iy2 = min(a["y2"], b["y2"])
    inter = max(0, ix2 - ix1) * max(0, iy2 - iy1)
    area_a = a["w"] * a["h"]
    area_b = b["w"] * b["h"]
    union = area_a + area_b - inter
    return inter / union if union > 0 else 0.0


def nms(detections: list, iou_threshold: float = 0.45) -> list:
    """Non-Maximum Suppression: elimina cajas redundantes sobre el mismo animal."""
    detections = sorted(detections, key=lambda d: d["conf"], reverse=True)
    kept = []
    for det in detections:
        if all(iou(det, k) < iou_threshold for k in kept):
            kept.append(det)
    return kept


def postprocess(output: np.ndarray, orig_w: int, orig_h: int) -> list:
    """
    YOLOv8 ONNX output: [1, 84, 8400]
      - filas 0-3 : cx, cy, w, h  (en píxeles del input 640x640)
      - filas 4-83: scores por clase COCO
    Aplica NMS para eliminar detecciones redundantes del mismo animal.
    """
    preds  = output[0]                  # (84, 8400)
    boxes  = preds[:4, :].T             # (8400, 4)
    scores = preds[4:, :].T             # (8400, 80)

    sx = orig_w / INPUT_SIZE
    sy = orig_h / INPUT_SIZE

    detections = []
    for i in range(scores.shape[0]):
        cls_id = int(np.argmax(scores[i]))
        conf   = float(scores[i, cls_id])

        if conf < MIN_CONF or cls_id != COCO_COW_CLS:
            continue

        cx, cy, w, h = boxes[i]
        x1 = (cx - w / 2) * sx
        y1 = (cy - h / 2) * sy
        x2 = (cx + w / 2) * sx
        y2 = (cy + h / 2) * sy

        detections.append({
            "conf": conf,
            "x1": x1, "y1": y1, "x2": x2, "y2": y2,
            "w": x2 - x1, "h": y2 - y1,
        })

    return nms(detections)


def estimar_peso(bbox_w, bbox_h, img_w, img_h, raza, edad_meses) -> dict:
    """Estima peso a partir de proporciones del bounding box + metadatos."""
    coverage = (bbox_w * bbox_h) / (img_w * img_h)
    aspect   = bbox_w / bbox_h if bbox_h > 0 else 1.6

    peso_base_raza = {
        "brahman": 480.0, "holstein": 550.0,
        "angus":   560.0, "nelore":   460.0, "jersey": 420.0,
    }
    peso_base = peso_base_raza.get(raza.lower().strip(), 490.0)

    if edad_meses <= 0:
        factor_edad = 1.0
    elif edad_meses < 12:
        factor_edad = 0.30
    elif edad_meses < 18:
        factor_edad = 0.50
    elif edad_meses < 24:
        factor_edad = 0.70
    elif edad_meses < 36:
        factor_edad = 0.85
    else:
        factor_edad = 1.0

    factor_cob    = 0.60 + coverage * 1.35
    factor_aspecto = 0.92 if aspect < 1.2 else 1.0

    peso    = peso_base * factor_edad * factor_cob * factor_aspecto
    peso    = max(80.0, min(750.0, peso))

    alzada  = 90 + (bbox_h / img_h) * 160
    alzada  = max(90.0, min(170.0, alzada))

    cc = max(1.0, min(5.0, round(2.0 + coverage * 5.0, 1)))

    return {
        "peso_estimado":      round(peso, 1),
        "alzada_estimada":    round(alzada, 1),
        "condicion_corporal": cc,
    }


@app.route("/detectar", methods=["POST"])
def detectar():
    if "imagen" not in request.files:
        return jsonify({"error": "No se recibió imagen", "codigo": "SIN_IMAGEN"}), 400

    raza       = request.form.get("raza", "brahman")
    edad_meses = int(request.form.get("edad", 0) or 0)

    try:
        img_bytes = request.files["imagen"].read()
        img       = Image.open(io.BytesIO(img_bytes)).convert("RGB")
    except Exception as e:
        return jsonify({"error": f"No se pudo leer la imagen: {e}", "codigo": "CALIDAD_INSUFICIENTE"}), 422

    img_w, img_h = img.size

    try:
        session = get_session()
        inp     = preprocess(img)
        name    = session.get_inputs()[0].name
        output  = session.run(None, {name: inp})
        vacas   = postprocess(output[0], img_w, img_h)
    except Exception as e:
        return jsonify({"error": f"Error en la detección: {e}"}), 500

    if len(vacas) == 0:
        return jsonify({
            "error": "No se detectó un bovino. Asegúrate de que el animal esté visible de perfil.",
            "codigo": "NO_BOVINO",
        }), 422

    if len(vacas) > 3:
        return jsonify({
            "error": "Se detectaron múltiples animales. Enfoca un solo bovino.",
            "codigo": "MULTIPLES_ANIMALES",
        }), 422

    mejor    = max(vacas, key=lambda v: v["conf"])
    coverage = (mejor["w"] * mejor["h"]) / (img_w * img_h)

    if coverage < 0.05:
        return jsonify({
            "error": "El animal está muy lejos o es muy pequeño. Acércate más.",
            "codigo": "COBERTURA_INSUFICIENTE",
        }), 422

    estimacion = estimar_peso(
        mejor["w"], mejor["h"], img_w, img_h, raza, edad_meses
    )

    return jsonify({
        "peso_estimado":      estimacion["peso_estimado"],
        "confianza":          round(mejor["conf"] * 100, 1),
        "alzada_estimada":    estimacion["alzada_estimada"],
        "condicion_corporal": estimacion["condicion_corporal"],
        "mensaje":            "Estimación generada por YOLOv8n ONNX",
    }), 200


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "modelo": MODEL_PATH}), 200


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port)
