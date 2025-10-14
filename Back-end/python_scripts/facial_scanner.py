#!/usr/bin/env python3
"""
Script de escaneo facial para el sistema biométrico
"""

import sys
import json
import time
import random

def simulate_face_scan(session_id):
    """Simula un escaneo facial"""
    time.sleep(1)  # Simular procesamiento
    
    return {
        "scan_completed": True,
        "landmarks": 68,
        "confidence": round(random.uniform(0.85, 0.99), 2),
        "processing_time": round(random.uniform(1.5, 3.0), 2),
        "face_id": f"face_{session_id}",
        "estimated_age": random.randint(18, 65),
        "gender": random.choice(["male", "female"]),
        "expression": random.choice(["neutral", "happy", "serious"]),
        "has_glasses": random.choice([True, False]),
        "image_path": f"/temp/face_{session_id}.jpg",
        "is_mock_data": False
    }

def simulate_document_scan(session_id):
    """Simula un escaneo de documento"""
    time.sleep(1)  # Simular procesamiento
    
    return {
        "scan_completed": True,
        "quality": random.choice(["Excelente", "Buena", "Aceptable"]),
        "extraction_success": True,
        "ocr_data": {
            "name": random.choice(["Juan Pérez", "María González", "Carlos López"]),
            "idNumber": f"{random.randint(10000000, 25000000)}-{random.randint(0,9)}",
            "birthDate": f"{random.randint(1980, 2000)}-{random.randint(1,12):02d}-{random.randint(1,28):02d}",
            "nationality": "Chilena"
        },
        "processing_time": round(random.uniform(1.0, 2.5), 2),
        "image_path": f"/temp/doc_{session_id}.jpg",
        "is_mock_data": False
    }

def main():
    if len(sys.argv) != 3:
        result = {
            "error": "Parámetros incorrectos",
            "usage": "facial_scanner.py [face|id] [session_id]"
        }
        print(json.dumps(result))
        sys.exit(1)
    
    scan_type = sys.argv[1]
    session_id = sys.argv[2]
    
    try:
        if scan_type == "face":
            result = simulate_face_scan(session_id)
        elif scan_type == "id":
            result = simulate_document_scan(session_id)
        else:
            result = {"error": f"Tipo de escaneo no válido: {scan_type}"}
        
        print(json.dumps(result, ensure_ascii=False))
        
    except Exception as e:
        error_result = {
            "error": f"Error en el script Python: {str(e)}"
        }
        print(json.dumps(error_result))
        sys.exit(1)

if __name__ == "__main__":
    main()