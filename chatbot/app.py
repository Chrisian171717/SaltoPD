from flask import Flask, request, jsonify, render_template
from datetime import datetime

app = Flask(__name__)

def responder(mensaje, nombre_usuario, denuncias):
    mensaje = mensaje.lower()
    respuestas = []

    if "hola" in mensaje:
        respuestas.append(f"¡Hola {nombre_usuario}! ¿Cómo estás hoy?")

    if "scanner" in mensaje:
        respuestas.append("El scanner sirve para saber el registro de las personas que pasan por la frontera. Si surge algún inconveniente (como negarse a dar su cédula o información), se pueden encontrar sus datos escaneando a la persona.")

    if any(palabra in mensaje for palabra in ["denuncia", "denuncias", "agregar denuncia", "corregir denuncia", "editar denuncia", "modificar denuncia"]):
        respuestas.append(
            "La pestaña Denuncias cumple con la función de registrar y mostrar los delitos cometidos por los civiles. "
            "Cada denuncia queda asociada a la persona en la base de datos, y a su vez se conecta con la pestaña Vehículos "
            "y con el Mapa. Esto permite que, por ejemplo, si un vehículo o una matrícula tienen una denuncia, el sistema pueda "
            "mostrar su ubicación en tiempo real junto con los policías que están de servicio."
            "Para agregar, eliminar o editar una denuncia hay un boton en el final de la denuncia."
        )

    if "civiles" in mensaje:
        respuestas.append("La pestaña Civiles muestra la información de todas las personas en la base de datos: nombre, apellido, cédula y denuncia cometida. Además, está vinculada con la pestaña Vehículos, ya que cada vehículo debe tener un propietario.")

    if "vehículos" in mensaje or "vehiculos" in mensaje:
        respuestas.append("La pestaña Vehículos muestra la matrícula y una breve descripción de cada vehículo. Está vinculada con Civiles, porque cada vehículo tiene un dueño registrado.")

    if "mapa" in mensaje:
        respuestas.append("La pestaña Mapa muestra la ubicación actual de todos los policías en servicio y de las matrículas con denuncias.")

    if "otras" in mensaje or "funciones" in mensaje:
        respuestas.append("Sí, debajo de estas pestañas hay un cuadro que muestra todos los policías que están en horario de trabajo.")

    if "hora" in mensaje:
        ahora = datetime.now().strftime("%H:%M:%S")
        respuestas.append(f"La hora actual es {ahora}.")

    if "fecha" in mensaje:
        hoy = datetime.now().strftime("%d/%m/%Y")
        respuestas.append(f"Hoy es {hoy}.")

    if "adiós" in mensaje or "adios" in mensaje:
        respuestas.append("¡Hasta luego! Fue un gusto hablar contigo.")

    if not respuestas:
        respuestas.append("No entendí eso, ¿puedes decirlo de otra manera?")

    return "\n".join(respuestas)


denuncias = ["Juan Pérez - Robo", "Ana Gómez - Estafa"]
historial = []

menu_inicial = [
    "Opciones:",
    "1. Saludar",
    "2. Finalidad del scanner",
    "3. Funcionalidad de denuncias",
    "4. Funcionalidad de civiles",
    "5. Funcionalidad de vehículos",
    "6. Funcionalidad de mapa",
    "7. ¿Hay otras funciones?",
    "8. Salir",
    "9. Charla libre"
]

@app.route("/")
def home():
    return render_template("index.html", menu_inicial=menu_inicial)

@app.route("/chat", methods=["POST"])
def chat():
    data = request.json
    mensaje_usuario = data["message"]
    nombre_usuario = data.get("nombre", "Usuario")

    menu_map = {
        "1": "hola",
        "2": "scanner",
        "3": "denuncias",
        "4": "civiles",
        "5": "vehículos",
        "6": "mapa",
        "7": "otras funciones"
    }
    if mensaje_usuario in menu_map:
        mensaje_usuario = menu_map[mensaje_usuario]

    respuesta = responder(mensaje_usuario, nombre_usuario, denuncias)
    historial.append({"usuario": nombre_usuario, "mensaje": mensaje_usuario})
    historial.append({"bot": respuesta})

    return jsonify({"reply": respuesta, "historial": historial})

if __name__ == "__main__":
    app.run(debug=True)
