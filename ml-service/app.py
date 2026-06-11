from flask import Flask, request, jsonify
from detector import detectar_vaca
import os

app = Flask(__name__)

CARPETA_UPLOADS = "uploads"

os.makedirs(CARPETA_UPLOADS, exist_ok=True)

@app.route("/")
def home():
    return {
        "mensaje": "Microservicio IA funcionando"
    }

@app.route("/detectar", methods=["POST"])
def detectar():

    if "imagen" not in request.files:
        return jsonify({
            "error": "No se envió imagen"
        }), 400

    imagen = request.files["imagen"]

    ruta = os.path.join(
        CARPETA_UPLOADS,
        imagen.filename
    )

    imagen.save(ruta)

    resultado = detectar_vaca(ruta)

    return jsonify(resultado)

if __name__ == "__main__":
    app.run(debug=True)