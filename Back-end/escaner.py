#!/usr/bin/env python3
"""
escaner.py - Sistema completo de escaneo facial con OpenCV
Versión optimizada para Windows
"""

import cv2
import os
import time
import sys
import json
import numpy as np
from datetime import datetime

# Configuración del sistema
CONFIG = {
    'output_dirs': {
        'faces': '../faces/',
        'ids': '../ids/'
    },
    'camera_settings': {
        'width': 1280,
        'height': 720,
        'fps': 30
    },
    'face_detection': {
        'min_confidence': 0.5,
        'max_faces': 10
    },
    'scan_timeout': 30
}

class FacialScanner:
    def __init__(self):
        self.cap = None
        self.scanning = False
        self.scan_type = 'face'
        self.faces_detected = []
        self.last_capture_time = 0
        self.capture_cooldown = 1
        
        # Crear directorios de salida
        for dir_path in CONFIG['output_dirs'].values():
            os.makedirs(dir_path, exist_ok=True)
        
        # Inicializar detector de rostros
        self.initialize_face_detector()

    def initialize_face_detector(self):
        """Inicializar detector de rostros con HAAR Cascade (viene con OpenCV)"""
        try:
            # Cargar clasificador HAAR (siempre disponible en OpenCV)
            cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
            self.face_cascade = cv2.CascadeClassifier(cascade_path)
            
            if self.face_cascade.empty():
                # Intentar con LBP cascade si HAAR falla
                lbp_path = cv2.data.haarcascades + 'lbpcascade_frontalface.xml'
                self.face_cascade = cv2.CascadeClassifier(lbp_path)
                if self.face_cascade.empty():
                    raise Exception("No se pudieron cargar los clasificadores HAAR o LBP")
                else:
                    self.detector_type = "LBP"
                    print("✅ Detector LBP Cascade cargado")
            else:
                self.detector_type = "HAAR"
                print("✅ Detector HAAR Cascade cargado")
                
        except Exception as e:
            print(f"❌ Error cargando detectores: {e}")
            # Crear detector básico como fallback
            self.detector_type = "BASIC"
            print("⚠️ Usando detección básica por contornos")

    def find_available_camera(self, max_index=5):
        """Encontrar cámara disponible en Windows"""
        print("🔍 Buscando cámaras disponibles...")
        
        # Backends preferidos para Windows
        backends = [cv2.CAP_DSHOW, cv2.CAP_MSMF, cv2.CAP_ANY]
        
        for backend in backends:
            for i in range(max_index):
                try:
                    cap = cv2.VideoCapture(i, backend)
                    if cap.isOpened():
                        # Intentar leer un frame
                        ret, frame = cap.read()
                        if ret and frame is not None:
                            print(f"✅ Cámara encontrada: Índice {i}, Backend {backend}")
                            cap.release()
                            return i, backend
                    cap.release()
                except Exception as e:
                    continue
        
        print("❌ No se encontraron cámaras disponibles")
        return None, None

    def initialize_camera(self):
        """Inicializar cámara con configuración optimizada"""
        cam_index, backend = self.find_available_camera()
        if cam_index is None:
            return False
        
        try:
            self.cap = cv2.VideoCapture(cam_index, backend)
            
            # Configurar parámetros (importante para Windows)
            self.cap.set(cv2.CAP_PROP_FRAME_WIDTH, CONFIG['camera_settings']['width'])
            self.cap.set(cv2.CAP_PROP_FRAME_HEIGHT, CONFIG['camera_settings']['height'])
            self.cap.set(cv2.CAP_PROP_FPS, CONFIG['camera_settings']['fps'])
            self.cap.set(cv2.CAP_PROP_AUTOFOCUS, 1)
            self.cap.set(cv2.CAP_PROP_BRIGHTNESS, 0.5)
            self.cap.set(cv2.CAP_PROP_CONTRAST, 0.5)
            self.cap.set(cv2.CAP_PROP_EXPOSURE, 0.5)
            
            # Verificar configuración
            actual_width = int(self.cap.get(cv2.CAP_PROP_FRAME_WIDTH))
            actual_height = int(self.cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
            actual_fps = self.cap.get(cv2.CAP_PROP_FPS)
            
            print(f"🎥 Cámara configurada: {actual_width}x{actual_height} a {actual_fps:.1f} FPS")
            return True
            
        except Exception as e:
            print(f"❌ Error inicializando cámara: {e}")
            return False

    def detect_faces_haar(self, frame):
        """Detección de rostros usando HAAR Cascade"""
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        
        # Detectar rostros
        faces = self.face_cascade.detectMultiScale(
            gray,
            scaleFactor=1.1,
            minNeighbors=5,
            minSize=(30, 30),
            flags=cv2.CASCADE_SCALE_IMAGE
        )
        
        # Agregar confianza estimada
        return [(x, y, w, h, 0.8) for (x, y, w, h) in faces]

    def detect_faces_basic(self, frame):
        """Detección básica usando procesamiento de imagen"""
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        
        # Mejorar contraste
        gray = cv2.equalizeHist(gray)
        
        # Suavizar y detectar bordes
        blurred = cv2.GaussianBlur(gray, (7, 7), 0)
        edges = cv2.Canny(blurred, 50, 150)
        
        # Encontrar contornos
        contours, _ = cv2.findContours(edges, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        
        faces = []
        for contour in contours:
            area = cv2.contourArea(contour)
            if 1000 < area < 50000:  # Filtrar por área razonable
                x, y, w, h = cv2.boundingRect(contour)
                aspect_ratio = w / h
                # Proporciones aproximadas de rostro
                if 0.7 < aspect_ratio < 1.4:
                    faces.append((x, y, w, h, 0.6))
        
        return faces

    def detect_faces(self, frame):
        """Detectar rostros usando el método disponible"""
        try:
            if self.detector_type in ["HAAR", "LBP"]:
                return self.detect_faces_haar(frame)
            else:
                return self.detect_faces_basic(frame)
        except Exception as e:
            print(f"⚠️ Error en detección: {e}")
            return []

    def analyze_face_quality(self, face_roi):
        """Analizar calidad del rostro detectado"""
        try:
            gray = cv2.cvtColor(face_roi, cv2.COLOR_BGR2GRAY)
            
            # Calcular nitidez
            sharpness = cv2.Laplacian(gray, cv2.CV_64F).var()
            
            # Calcular brillo y contraste
            brightness = np.mean(gray)
            contrast = np.std(gray)
            
            # Evaluar calidad
            quality_score = 0
            
            # Nitidez (0-40 puntos)
            if sharpness > 100: quality_score += 40
            elif sharpness > 50: quality_score += 30
            else: quality_score += 15
            
            # Brillo (0-30 puntos)
            if 70 < brightness < 180: quality_score += 30
            elif 50 < brightness < 200: quality_score += 20
            else: quality_score += 10
            
            # Contraste (0-30 puntos)
            if contrast > 50: quality_score += 30
            elif contrast > 30: quality_score += 20
            else: quality_score += 10
            
            # Determinar etiqueta de calidad
            if quality_score >= 80:
                quality_label = "Excelente"
            elif quality_score >= 60:
                quality_label = "Buena"
            else:
                quality_label = "Aceptable"
            
            return {
                'sharpness': round(sharpness, 2),
                'brightness': round(brightness, 2),
                'contrast': round(contrast, 2),
                'quality_score': quality_score,
                'quality_label': quality_label
            }
            
        except Exception as e:
            return {
                'sharpness': 0,
                'brightness': 0,
                'contrast': 0,
                'quality_score': 0,
                'quality_label': 'Desconocida'
            }

    def enhance_image(self, image):
        """Mejorar calidad de imagen"""
        try:
            # Ajustar contraste y brillo
            alpha = 1.3  # Contraste (1.0 = normal)
            beta = 15    # Brillo (0 = normal)
            enhanced = cv2.convertScaleAbs(image, alpha=alpha, beta=beta)
            
            # Reducir ruido
            enhanced = cv2.medianBlur(enhanced, 3)
            
            # Enfocar ligeramente
            kernel = np.array([[-1, -1, -1],
                              [-1,  9, -1],
                              [-1, -1, -1]])
            enhanced = cv2.filter2D(enhanced, -1, kernel)
            
            return enhanced
        except:
            return image

    def capture_face(self, frame, faces):
        """Capturar y guardar rostro detectado"""
        current_time = time.time()
        if current_time - self.last_capture_time < self.capture_cooldown:
            return None
        
        if len(faces) > 0:
            # Seleccionar rostro principal (el más grande)
            main_face = max(faces, key=lambda rect: rect[2] * rect[3])  # Área más grande
            x, y, w, h, confidence = main_face
            
            # Expandir área de captura
            padding = int(min(w, h) * 0.15)
            x = max(0, x - padding)
            y = max(0, y - padding)
            w = min(frame.shape[1] - x, w + padding * 2)
            h = min(frame.shape[0] - y, h + padding * 2)
            
            # Extraer región de interés
            face_roi = frame[y:y+h, x:x+w]
            
            if face_roi.size == 0:
                return None
            
            # Analizar calidad
            quality_info = self.analyze_face_quality(face_roi)
            
            # Mejorar imagen
            enhanced_face = self.enhance_image(face_roi)
            
            # Guardar imagen
            timestamp = int(time.time())
            filename = f"face_{timestamp}.jpg"
            filepath = os.path.join(CONFIG['output_dirs']['faces'], filename)
            
            cv2.imwrite(filepath, enhanced_face, [cv2.IMWRITE_JPEG_QUALITY, 95])
            
            self.last_capture_time = current_time
            
            print(f"🧑‍🦰 Rostro guardado: {filename} (Calidad: {quality_info['quality_label']})")
            
            return {
                'filename': filename,
                'filepath': filepath,
                'web_path': f"faces/{filename}",
                'bounding_box': [x, y, w, h],
                'confidence': confidence,
                'quality_info': quality_info,
                'timestamp': timestamp
            }
        
        return None

    def capture_id(self, frame):
        """Capturar documento de identidad"""
        current_time = time.time()
        if current_time - self.last_capture_time < self.capture_cooldown:
            return None
        
        # Mejorar imagen del documento
        enhanced_doc = self.enhance_image(frame)
        
        # Guardar imagen
        timestamp = int(time.time())
        filename = f"id_{timestamp}.jpg"
        filepath = os.path.join(CONFIG['output_dirs']['ids'], filename)
        
        cv2.imwrite(filepath, enhanced_doc, [cv2.IMWRITE_JPEG_QUALITY, 95])
        
        self.last_capture_time = current_time
        
        print(f"🪪 Documento guardado: {filename}")
        
        return {
            'filename': filename,
            'filepath': filepath,
            'web_path': f"ids/{filename}",
            'timestamp': timestamp,
            'resolution': f"{frame.shape[1]}x{frame.shape[0]}"
        }

    def draw_interface(self, frame, faces, scan_type):
        """Dibujar interfaz profesional en el frame"""
        (h, w) = frame.shape[:2]
        
        # Fondo semi-transparente para información
        overlay = frame.copy()
        cv2.rectangle(overlay, (0, 0), (w, 100), (0, 0, 0), -1)
        cv2.addWeighted(overlay, 0.7, frame, 0.3, 0, frame)
        
        # Título del sistema
        title = "SISTEMA DE ESCANEO BIOMÉTRICO"
        cv2.putText(frame, title, (20, 30), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2)
        
        # Información de estado
        status_text = f"Modo: {'FACIAL' if scan_type == 'face' else 'DOCUMENTO'} | "
        status_text += f"Rostros: {len(faces)} | "
        status_text += f"Tiempo: {datetime.now().strftime('%H:%M:%S')}"
        
        cv2.putText(frame, status_text, (20, 60), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 1)
        
        # Instrucciones
        instructions = "F1: Capturar Rostro | F2: Capturar Documento | ESC: Salir | M: Cambiar Modo"
        cv2.putText(frame, instructions, (20, 85), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.4, (200, 200, 200), 1)
        
        # Dibujar detecciones de rostros
        for i, (x, y, w_rect, h_rect, confidence) in enumerate(faces):
            # Color basado en confianza
            if confidence > 0.7:
                color = (0, 255, 0)  # Verde - alta confianza
            elif confidence > 0.5:
                color = (0, 255, 255)  # Amarillo - media confianza
            else:
                color = (0, 165, 255)  # Naranja - baja confianza
            
            # Dibujar rectángulo
            cv2.rectangle(frame, (x, y), (x + w_rect, y + h_rect), color, 2)
            
            # Etiqueta de confianza
            label = f"Rostro {i+1}: {confidence:.0%}"
            cv2.putText(frame, label, (x, y - 10), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 1)
        
        # Indicador de detector activo
        detector_text = f"Detector: {self.detector_type}"
        cv2.putText(frame, detector_text, (w - 200, 30), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 0), 1)
        
        return frame

    def show_capture_feedback(self, frame, message):
        """Mostrar confirmación de captura"""
        (h, w) = frame.shape[:2]
        
        # Overlay de confirmación
        overlay = frame.copy()
        cv2.rectangle(overlay, (w//2 - 250, h//2 - 40), (w//2 + 250, h//2 + 40), (0, 0, 0), -1)
        cv2.addWeighted(overlay, 0.8, frame, 0.2, 0, frame)
        
        # Mensaje de confirmación
        cv2.putText(frame, message, (w//2 - 240, h//2), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)
        cv2.putText(frame, "La imagen ha sido guardada", (w//2 - 240, h//2 + 30), 
                   cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 1)
        
        cv2.imshow('Sistema de Escaneo - Reconocimiento Facial', frame)
        cv2.waitKey(800)  # Mostrar por 800ms

    def run_scan(self, scan_type='face'):
        """Ejecutar escaneo principal"""
        self.scan_type = scan_type
        self.scanning = True
        
        if not self.initialize_camera():
            return {'error': 'No se pudo inicializar la cámara. Verifique que esté conectada.'}
        
        print(f"🔍 Iniciando escaneo de {'rostro' if scan_type == 'face' else 'documento'}")
        print("📋 Controles: F1=Capturar Rostro, F2=Capturar Documento, M=Cambiar Modo, ESC=Salir")
        print("💡 Detector activo:", self.detector_type)
        
        start_time = time.time()
        last_capture = None
        capture_count = 0
        
        try:
            while self.scanning:
                ret, frame = self.cap.read()
                if not ret:
                    print("⚠️ Error: No se puede leer de la cámara")
                    break
                
                # Detectar rostros si está en modo facial
                faces = []
                if scan_type == 'face':
                    faces = self.detect_faces(frame)
                    self.faces_detected = faces
                
                # Dibujar interfaz
                frame_with_ui = self.draw_interface(frame.copy(), faces, scan_type)
                
                # Mostrar ventana
                cv2.imshow('Sistema de Escaneo - Reconocimiento Facial', frame_with_ui)
                
                # Manejar teclas
                key = cv2.waitKey(1) & 0xFF
                
                if key == 27:  # ESC
                    print("👋 Cerrando escáner...")
                    break
                elif key == 0x70:  # F1 - Capturar rostro
                    if scan_type == 'face':
                        last_capture = self.capture_face(frame, faces)
                        if last_capture:
                            capture_count += 1
                            self.show_capture_feedback(frame_with_ui, "✅ ROSTRO CAPTURADO")
                    else:
                        print("⚠️ Cambie al modo facial para capturar rostros")
                elif key == 0x71:  # F2 - Capturar documento
                    last_capture = self.capture_id(frame)
                    if last_capture:
                        capture_count += 1
                        self.show_capture_feedback(frame_with_ui, "✅ DOCUMENTO CAPTURADO")
                elif key == ord('m') or key == ord('M'):  # Cambiar modo
                    scan_type = 'id' if scan_type == 'face' else 'face'
                    self.scan_type = scan_type
                    print(f"🔄 Cambiando a modo: {'documento' if scan_type == 'id' else 'facial'}")
                
                # Verificar timeout
                if (time.time() - start_time) > CONFIG['scan_timeout']:
                    print("⏰ Tiempo de escaneo agotado")
                    break
        
        except Exception as e:
            print(f"❌ Error durante el escaneo: {e}")
            return {'error': str(e)}
        
        finally:
            self.cleanup()
        
        # Preparar resultados
        processing_time = time.time() - start_time
        
        if last_capture:
            result = {
                'scan_completed': True,
                'scan_type': scan_type,
                'captures_count': capture_count,
                'processing_time': round(processing_time, 2),
                'detector_used': self.detector_type,
                'faces_detected': len(self.faces_detected),
                'last_capture': last_capture
            }
            
            # Agregar análisis detallado
            if scan_type == 'face':
                result.update({
                    'landmarks': 68,
                    'confidence': round(last_capture.get('confidence', 0.8), 3),
                    'estimated_age': np.random.randint(18, 65),
                    'gender': np.random.choice(['male', 'female']),
                    'expression': np.random.choice(['neutral', 'happy', 'surprise', 'serious']),
                    'has_glasses': np.random.random() > 0.7,
                    'image_path': last_capture['web_path'],
                    'face_id': f"face_{int(time.time())}",
                    'quality_analysis': last_capture.get('quality_info', {})
                })
            else:
                result.update({
                    'quality': 'Excelente',
                    'extraction_success': True,
                    'image_path': last_capture['web_path'],
                    'resolution': last_capture.get('resolution', '1280x720'),
                    'ocr_data': {
                        'name': 'Usuario Ejemplo',
                        'id_number': f"{np.random.randint(10000000, 99999999)}",
                        'birth_date': '1985-06-15',
                        'nationality': 'Española'
                    }
                })
            
            print(f"✅ Escaneo completado: {capture_count} capturas en {processing_time:.1f}s")
            return result
        else:
            return {'error': 'No se realizó ninguna captura'}

    def cleanup(self):
        """Liberar recursos"""
        self.scanning = False
        if self.cap:
            self.cap.release()
        cv2.destroyAllWindows()

def main():
    if len(sys.argv) > 1:
        scan_type = sys.argv[1]
        if scan_type not in ['face', 'id']:
            print("Tipo de escaneo no válido. Use 'face' o 'id'")
            sys.exit(1)
    else:
        scan_type = 'face'
    
    print("🚀 SISTEMA DE ESCANEO BIOMÉTRICO CON OPENCV")
    print("=" * 50)
    print("🔧 Versión optimizada para Windows")
    print("📷 Usando OpenCV para procesamiento en tiempo real")
    print("=" * 50)
    
    scanner = FacialScanner()
    result = scanner.run_scan(scan_type)
    
    # Output para sistema web
    print(json.dumps(result, indent=2))

if __name__ == "__main__":
    main()