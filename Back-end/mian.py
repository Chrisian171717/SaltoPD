

import os
import cv2


# Cargar el clasificador de rostros (asegúrate de que el archivo esté en la misma carpeta)
face_cascade = cv2.CascadeClassifier('haarcascade_frontalface_default.xml')
if face_cascade.empty():
    print("Error: No se pudo cargar el clasificador de rostros. Asegúrate de que 'haarcascade_frontalface_default.xml' esté en la carpeta correcta.")
    exit()

# Intenta abrir la cámara
cap = cv2.VideoCapture(0)
OPENCV_LOG_LEVEL=0

# Verificar si la cámara se abrió correctamente
if not cap.isOpened():
    print("Error: No se pudo abrir la cámara.")
    exit()

while True:
    # Capturar un frame de la cámara
    ret, frame = cap.read()
    
    # Si la captura del frame falla, salimos del bucle
    if not ret:
        print("Error: No se pudo leer el frame. La conexión se perdió.")
        break
    
    # Convertir la imagen a escala de grises para la detección de rostros
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    
    # Detectar rostros en la imagen en escala de grises
    faces = face_cascade.detectMultiScale(gray, 1.1, 4)
    
    # Dibujar un rectángulo alrededor de cada rostro detectado
    for (x, y, w, h) in faces:
        cv2.rectangle(frame, (x, y), (x + w, y + h), (255, 0, 0), 2)
    
    # Mostrar la imagen con los rectángulos
    cv2.imshow('Deteccion de Rostros', frame)
    
    # Salir del bucle al presionar la tecla 'q'
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

# Liberar los recursos de la cámara y cerrar las ventanas
cap.release()
cv2.destroyAllWindows()

    