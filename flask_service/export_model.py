"""
Script de build — se ejecuta UNA SOLA VEZ durante el deploy en Render.
Descarga yolov8n.pt y lo exporta a ONNX. Después PyTorch se desinstala
para reducir el uso de RAM en runtime a ~100 MB.
"""
from ultralytics import YOLO
import os

model = YOLO("yolov8n.pt")          # descarga ~6 MB si no existe
model.export(format="onnx", imgsz=640, simplify=True)
print("✅ Modelo exportado a yolov8n.onnx")

assert os.path.exists("yolov8n.onnx"), "ERROR: no se generó yolov8n.onnx"
