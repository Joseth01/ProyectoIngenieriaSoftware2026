from ultralytics import YOLO
import cv2

modelo = YOLO("yolov8n-seg.pt")

def detectar_vaca(ruta_imagen):

    imagen = cv2.imread(ruta_imagen)

    if imagen is None:
        return {
            "error": "No se pudo leer la imagen"
        }

    resultados = modelo.predict(
        source=imagen,
        conf=0.4,
        classes=[19],
        verbose=False
    )

    resultado = resultados[0]

    cantidad_vacas = len(resultado.boxes)

    if cantidad_vacas == 0:
        return {
            "deteccion_exitosa": False,
            "mensaje": "No se detectó ninguna vaca"
        }

    return {
        "deteccion_exitosa": True,
        "vacas_detectadas": cantidad_vacas,
        "peso_estimado": 420
    }