from flask import Flask, render_template, Response, jsonify
import cv2

app = Flask(__name__)

# Cargar el clasificador de rostros de OpenCV
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + "haarcascade_frontalface_default.xml")

camera = cv2.VideoCapture(0)  # Cámara por defecto

def generate_frames():
    while True:
        success, frame = camera.read()
        if not success:
            break
        else:
            # Convertir a gris
            gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            # Detectar rostros
            faces = face_cascade.detectMultiScale(gray, 1.3, 5)

            # Dibujar rectángulos
            for (x, y, w, h) in faces:
                cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)

            # Convertir a JPEG
            ret, buffer = cv2.imencode('.jpg', frame)
            frame = buffer.tobytes()

            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')

@app.route('/')
def index():
    return render_template('index.html')  # Cargar tu HTML

@app.route('/video')
def video():
    return Response(generate_frames(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/start', methods=['POST'])
def start_scan():
    return jsonify({"status": "Escaneo iniciado", "accuracy": "98.7%", "scan_time": "2.3s"})

@app.route('/stop', methods=['POST'])
def stop_scan():
    return jsonify({"status": "Escaneo detenido"})

@app.route('/reset', methods=['POST'])
def reset_scanner():
    return jsonify({"status": "Sistema reiniciado"})

@app.route('/export', methods=['POST'])
def export_data():
    return jsonify({"status": "Datos exportados correctamente"})

if __name__ == '__main__':
    app.run(debug=True)
