from ultralytics import YOLO

print("Cargando modelo YOLO...")

modelo = YOLO("yolov8n-seg.pt")

print("Modelo cargado correctamente")