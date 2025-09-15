import cv2
import os
import time

# Detecta la primera cámara disponible probando distintos backends
def find_available_camera(max_index=5):
    backends = [cv2.CAP_ANY, cv2.CAP_MSMF, cv2.CAP_DSHOW]
    for backend in backends:
        for i in range(max_index):
            cap = cv2.VideoCapture(i, backend)
            if cap.isOpened():
                ret, _ = cap.read()
                if ret:
                    cap.release()
                    print(f"✅ Cámara encontrada en índice {i} con backend {backend}")
                    return i, backend
            cap.release()
    return None, None

def scan_face_and_id():
    cv2.setUseOptimized(True)  # Activa optimización (mejor rendimiento)

    cam_index, backend = find_available_camera()
    if cam_index is None:
        print("❌ No se encontró ninguna cámara disponible.")
        return

    # Crear carpetas de salida
    os.makedirs("faces", exist_ok=True)
    os.makedirs("ids", exist_ok=True)

    cap = cv2.VideoCapture(cam_index, backend)

    # Modelo DNN de detección de rostros (SSD basado en ResNet10)
    modelFile = cv2.data.haarcascades + "../dnn/res10_300x300_ssd_iter_140000.caffemodel"
    configFile = cv2.data.haarcascades + "../dnn/deploy.prototxt"
    net = cv2.dnn.readNetFromCaffe(configFile, modelFile)

    print(f"🎥 Usando cámara en índice {cam_index} con backend {backend}")
    print("Presiona 'f' para escanear rostro, 'i' para escanear ID, 'q' para salir.")

    while True:
        ret, frame = cap.read()
        if not ret:
            print("⚠️ Error al capturar el frame. Verifica la cámara.")
            break

        (h, w) = frame.shape[:2]
        blob = cv2.dnn.blobFromImage(cv2.resize(frame, (300, 300)), 1.0,
                                     (300, 300), (104.0, 177.0, 123.0))
        net.setInput(blob)
        detections = net.forward()

        faces = []
        for i in range(detections.shape[2]):
            confidence = detections[0, 0, i, 2]
            if confidence > 0.6:  # confianza mínima 60%
                box = detections[0, 0, i, 3:7] * [w, h, w, h]
                (x, y, x2, y2) = box.astype("int")
                faces.append((x, y, x2 - x, y2 - y))
                cv2.rectangle(frame, (x, y), (x2, y2), (0, 255, 0), 2)

        # Mostrar cantidad de rostros detectados
        cv2.putText(frame, f"Rostros detectados: {len(faces)}",
                    (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)

        # Mostrar instrucciones en pantalla
        cv2.putText(frame, "Presiona 'f'=rostro, 'i'=ID, 'q'=salir",
                    (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

        cv2.imshow('Escáner', frame)
        key = cv2.waitKey(1) & 0xFF

        if key == ord('f'):
            if len(faces) > 0:
                for i, (x, y, w_, h_) in enumerate(faces):
                    face_img = frame[y:y+h_, x:x+w_]
                    filename = f"faces/face_{int(time.time())}_{i}.jpg"
                    cv2.imwrite(filename, face_img)
                    print(f"🧑‍🦰 Rostro {i+1} guardado como '{filename}'")
            else:
                print("🚫 No se detectó ningún rostro.")
        elif key == ord('i'):
            filename = f"ids/id_{int(time.time())}.jpg"
            cv2.imwrite(filename, frame)
            print(f"🪪 ID escaneada y guardada como '{filename}'")
        elif key == ord('q'):
            print("👋 Cerrando escáner...")
            break

    cap.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    scan_face_and_id()
