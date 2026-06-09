"""
BovWeight – Microservicio de estimación de peso bovino
Flask + YOLOv8n (COCO pretrained, clase 19 = cow)

POST /detectar
  Body (multipart/form-data):
    imagen  : archivo de imagen del bovino
    raza    : (opcional) brahman | holstein | angus | nelore
    edad    : (opcional) edad en meses

Response 200:
  {
    "peso_estimado": 385.4,
    "confianza": 88.2,
    "alzada_estimada": 138.5,
    "condicion_corporal": 3.2,
    "mensaje": "..."
  }

Response 422:
  {
    "error": "...",
    "codigo": "NO_BOVINO" | "MULTIPLES_ANIMALES" | "CALIDAD_INSUFICIENTE"
  }
"""

import io
import os

from flask import Flask, request, jsonify
from PIL import Image
from ultralytics import YOLO

app = Flask(__name__)

# YOLOv8n COCO pretrained se descarga automáticamente la primera vez (~6 MB)
# Clase 19 en COCO = "cow"
MODEL_PATH = os.environ.get("MODEL_PATH", "yolov8n.pt")
COCO_COW_CLASS = 19
MIN_CONFIDENCE = 0.40

_model = None


def get_model() -> YOLO:
    global _model
    if _model is None:
        _model = YOLO(MODEL_PATH)
    return _model


def estimar_peso(
    bbox_w: float,
    bbox_h: float,
    img_w: float,
    img_h: float,
    raza: str,
    edad_meses: int,
) -> dict:
    """
    Estima peso a partir de proporciones del bounding box + metadatos.

    Lógica:
      - coverage  = fracción del frame que ocupa el bovino
      - ratio     = ancho / alto del bbox (animales de perfil ~1.6–2.0)
      - Peso base según raza (valores promedio adulto)
      - Ajuste por edad (animales jóvenes pesan menos)
      - Ajuste por cobertura relativa (animal más grande en frame → más cerca → más grande)
    """
    coverage = (bbox_w * bbox_h) / (img_w * img_h)
    aspect   = bbox_w / bbox_h if bbox_h > 0 else 1.6

    # Pesos base por raza (kg, adulto)
    peso_base_raza = {
        "brahman":  480.0,
        "holstein": 550.0,
        "angus":    560.0,
        "nelore":   460.0,
        "jersey":   420.0,
    }
    peso_base = peso_base_raza.get(raza.lower().strip(), 490.0)

    # Factor de madurez: < 12 meses = cría, 12–24 = novillo, > 24 = adulto
    if edad_meses <= 0:
        factor_edad = 1.0          # sin información, asumir adulto
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

    # Factor de cobertura: calibrado para que coverage ~0.30 = animal adulto
    # de perfil a ~2 m de distancia
    factor_cobertura = 0.60 + coverage * 1.35

    # Factor de aspecto: animales de perfil tienen ratio ~1.6–2.0
    # Si el ratio es muy bajo podría ser vista frontal (menos precisa)
    factor_aspecto = 0.92 if aspect < 1.2 else 1.0

    peso = peso_base * factor_edad * factor_cobertura * factor_aspecto

    # Alzada estimada (cm) — proporcional al alto del bbox respecto al frame
    altura_ratio = bbox_h / img_h
    alzada = 90 + altura_ratio * 160          # rango ~90–170 cm
    alzada = max(90.0, min(170.0, alzada))

    # Condición corporal 1–5: correlaciona con coverage y cobertura
    cc = 2.0 + coverage * 5.0
    cc = max(1.0, min(5.0, round(cc, 1)))

    # Peso dentro de rango bovino adulto razonable
    peso = max(80.0, min(750.0, peso))

    return {
        "peso_estimado":     round(peso, 1),
        "alzada_estimada":   round(alzada, 1),
        "condicion_corporal": cc,
    }


@app.route("/detectar", methods=["POST"])
def detectar():
    if "imagen" not in request.files:
        return jsonify({"error": "No se recibió imagen", "codigo": "SIN_IMAGEN"}), 400

    raza        = request.form.get("raza", "brahman")
    edad_meses  = int(request.form.get("edad", 0) or 0)

    try:
        img_bytes = request.files["imagen"].read()
        img = Image.open(io.BytesIO(img_bytes)).convert("RGB")
    except Exception as e:
        return jsonify({"error": f"No se pudo leer la imagen: {str(e)}", "codigo": "CALIDAD_INSUFICIENTE"}), 422

    img_w, img_h = img.size

    # Detectar con YOLOv8
    model   = get_model()
    results = model(img, verbose=False)

    vacas = []
    for result in results:
        for box in result.boxes:
            cls  = int(box.cls[0])
            conf = float(box.conf[0])
            if cls == COCO_COW_CLASS and conf >= MIN_CONFIDENCE:
                x1, y1, x2, y2 = box.xyxy[0].tolist()
                vacas.append({
                    "conf": conf,
                    "x1": x1, "y1": y1, "x2": x2, "y2": y2,
                    "w": x2 - x1, "h": y2 - y1,
                })

    if len(vacas) == 0:
        return jsonify({
            "error": "No se detectó un bovino en la imagen. Asegúrate de que el animal esté visible de perfil.",
            "codigo": "NO_BOVINO",
        }), 422

    if len(vacas) > 3:
        return jsonify({
            "error": "Se detectaron múltiples animales. Enfoca un solo bovino.",
            "codigo": "MULTIPLES_ANIMALES",
        }), 422

    # Usar la detección con mayor confianza
    mejor = max(vacas, key=lambda v: v["conf"])

    # Verificar que el animal ocupe un porcentaje razonable del frame
    coverage = (mejor["w"] * mejor["h"]) / (img_w * img_h)
    if coverage < 0.05:
        return jsonify({
            "error": "El animal está muy lejos o es muy pequeño. Acércate más.",
            "codigo": "COBERTURA_INSUFICIENTE",
        }), 422

    estimacion = estimar_peso(
        bbox_w     = mejor["w"],
        bbox_h     = mejor["h"],
        img_w      = float(img_w),
        img_h      = float(img_h),
        raza       = raza,
        edad_meses = edad_meses,
    )

    confianza_yolo = round(mejor["conf"] * 100, 1)

    return jsonify({
        "peso_estimado":      estimacion["peso_estimado"],
        "confianza":          confianza_yolo,
        "alzada_estimada":    estimacion["alzada_estimada"],
        "condicion_corporal": estimacion["condicion_corporal"],
        "mensaje":            "Estimación generada por YOLOv8 (COCO cow detection)",
    }), 200


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "modelo": MODEL_PATH}), 200


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port)
