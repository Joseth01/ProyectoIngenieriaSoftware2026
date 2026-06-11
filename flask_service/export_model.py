"""
Script de build — se ejecuta UNA SOLA VEZ durante el deploy en Render.
Descarga yolov8n.pt y lo exporta a ONNX. Después PyTorch se desinstala
para reducir el uso de RAM en runtime a ~100 MB.
"""
from ultralytics import YOLO
import os
import shutil

TARGET = "yolov8n.onnx"

model       = YOLO("yolov8n.pt")          # descarga ~6 MB si no existe
export_path = model.export(format="onnx", imgsz=640, simplify=True)

print(f"Modelo exportado en: {export_path}")

# export() devuelve la ruta real; copiarla al directorio de trabajo si difiere
export_str = str(export_path)
if os.path.abspath(export_str) != os.path.abspath(TARGET):
    shutil.copy(export_str, TARGET)
    print(f"Copiado a {TARGET}")

assert os.path.exists(TARGET), f"ERROR: no se generó {TARGET}"
print(f"OK: {TARGET} listo ({os.path.getsize(TARGET) // 1024} KB)")
