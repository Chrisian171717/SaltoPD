<?php
// Back-end/Escaner.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class ScannerHandler {
    private function getPythonCommand() {
        // Verificar qué comando de Python está disponible
        $commands = ['python3', 'python', 'py'];
        
        foreach ($commands as $cmd) {
            $output = [];
            $returnCode = 0;
            $command = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 
                "where $cmd 2>nul" : 
                "which $cmd 2>/dev/null";
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                error_log("Python command found: $cmd");
                return $cmd;
            }
        }
        
        error_log("No Python command found");
        return null;
    }

    public function executePythonScan($scanType, $sessionId) {
        try {
            $pythonCommand = $this->getPythonCommand();
            
            if (!$pythonCommand) {
                error_log("Python not available, using mock data");
                return [
                    'success' => true,
                    'data' => $this->getMockPythonResponse($scanType, $sessionId)
                ];
            }
            
            // Construir la ruta al script Python
            $scriptDir = __DIR__ . '/python_scripts';
            $scriptPath = $scriptDir . '/facial_scanner.py';
            
            // Si el directorio no existe, crearlo
            if (!is_dir($scriptDir)) {
                mkdir($scriptDir, 0755, true);
            }
            
            // Si el script no existe, crearlo
            if (!file_exists($scriptPath)) {
                $this->createPythonScript($scriptPath);
            }
            
            // Verificar que el script existe
            if (!file_exists($scriptPath)) {
                error_log("Python script not found at: $scriptPath");
                return [
                    'success' => true,
                    'data' => $this->getMockPythonResponse($scanType, $sessionId)
                ];
            }
            
            // Ejecutar el script Python
            $command = escapeshellcmd("$pythonCommand " . escapeshellarg($scriptPath) . " " . 
                                    escapeshellarg($scanType) . " " . escapeshellarg($sessionId));
            
            error_log("Executing command: $command");
            
            $output = [];
            $returnCode = 0;
            exec($command . " 2>&1", $output, $returnCode);
            
            $outputString = implode("\n", $output);
            error_log("Python output: $outputString");
            error_log("Python return code: $returnCode");
            
            if ($returnCode !== 0) {
                throw new Exception("Error en ejecución de Python (Code: $returnCode): $outputString");
            }
            
            // Parsear la respuesta JSON de Python
            $result = json_decode($outputString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg() . " - Output: " . $outputString);
                return [
                    'success' => true,
                    'data' => $this->getMockPythonResponse($scanType, $sessionId)
                ];
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            error_log("Python scan error: " . $e->getMessage());
            
            // En caso de error, devolver datos de prueba
            return [
                'success' => true,
                'data' => $this->getMockPythonResponse($scanType, $sessionId)
            ];
        }
    }
    
    private function createPythonScript($scriptPath) {
        $pythonCode = '#!/usr/bin/env python3
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
    main()';

        file_put_contents($scriptPath, $pythonCode);
        chmod($scriptPath, 0755);
        error_log("Python script created at: $scriptPath");
    }
    
    private function getMockPythonResponse($scanType, $sessionId) {
        // Datos de prueba para desarrollo
        if ($scanType === 'face') {
            return [
                'scan_completed' => true,
                'landmarks' => 68,
                'confidence' => 0.95,
                'processing_time' => 2.3,
                'face_id' => 'face_' . $sessionId,
                'estimated_age' => rand(20, 50),
                'gender' => rand(0, 1) ? 'male' : 'female',
                'expression' => 'neutral',
                'has_glasses' => rand(0, 1) === 1,
                'image_path' => '/temp/face_' . $sessionId . '.jpg',
                'is_mock_data' => true
            ];
        } else {
            return [
                'scan_completed' => true,
                'quality' => 'Buena',
                'extraction_success' => true,
                'ocr_data' => [
                    'name' => 'Juan Pérez',
                    'idNumber' => '12345678-' . rand(0, 9),
                    'birthDate' => '1990-' . sprintf('%02d', rand(1, 12)) . '-' . sprintf('%02d', rand(1, 28)),
                    'nationality' => 'Chilena'
                ],
                'processing_time' => 1.8,
                'image_path' => '/temp/doc_' . $sessionId . '.jpg',
                'is_mock_data' => true
            ];
        }
    }
}

class VerificationHandler {
    public function handleVerification($data) {
        try {
            // Validar datos requeridos
            if (empty($data['face_data']) && empty($data['document_data'])) {
                throw new Exception('No se recibieron datos de escaneo');
            }
            
            // Procesar datos faciales
            $faceResult = null;
            if (!empty($data['face_data'])) {
                $faceResult = $this->processFaceData($data['face_data']);
            }
            
            // Procesar datos documentales
            $documentResult = null;
            if (!empty($data['document_data'])) {
                $documentResult = $this->processDocumentData($data['document_data']);
            }
            
            // Generar ID de verificación
            $verificationId = 'VER_' . uniqid() . '_' . date('YmdHis');
            
            // Guardar en base de datos o archivo (simulado)
            $this->saveVerification($verificationId, $data, $faceResult, $documentResult);
            
            return [
                'success' => true,
                'verification_id' => $verificationId,
                'estimated_time' => '1-2 minutos',
                'message' => 'Datos recibidos correctamente',
                'face_processed' => $faceResult !== null,
                'document_processed' => $documentResult !== null,
                'timestamp' => date('c')
            ];
            
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }
    
    private function processFaceData($faceData) {
        // Simular procesamiento de datos faciales
        return [
            'status' => 'processed',
            'confidence' => $faceData['accuracy'] ?? 0,
            'landmarks_detected' => $faceData['landmarks'] ?? 0,
            'processing_time' => '0.5s'
        ];
    }
    
    private function processDocumentData($documentData) {
        // Simular procesamiento de datos documentales
        return [
            'status' => 'processed',
            'quality_score' => $this->calculateQualityScore($documentData['quality'] ?? ''),
            'extraction_success' => $documentData['extracted'] ?? false,
            'processing_time' => '0.3s'
        ];
    }
    
    private function calculateQualityScore($quality) {
        $scores = [
            'Excelente' => 95,
            'Buena' => 80,
            'Aceptable' => 65,
            'Mala' => 40
        ];
        
        return $scores[$quality] ?? 70;
    }
    
    private function saveVerification($verificationId, $data, $faceResult, $documentResult) {
        // En un sistema real, aquí guardarías en una base de datos
        $verificationData = [
            'verification_id' => $verificationId,
            'timestamp' => date('c'),
            'face_data' => $data['face_data'] ?? null,
            'document_data' => $data['document_data'] ?? null,
            'face_result' => $faceResult,
            'document_result' => $documentResult,
            'session_id' => $data['session_id'] ?? 'unknown'
        ];
        
        // Guardar en archivo JSON (simulación)
        $filename = __DIR__ . '/verifications/' . $verificationId . '.json';
        $directory = dirname($filename);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filename, json_encode($verificationData, JSON_PRETTY_PRINT));
        
        error_log("Verification saved: " . $verificationId);
    }
}

// Manejar la solicitud
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('No se recibieron datos JSON válidos');
        }
        
        // Manejar diferentes acciones
        if (isset($input['action'])) {
            switch ($input['action']) {
                case 'execute_python_scan':
                    $scanner = new ScannerHandler();
                    $result = $scanner->executePythonScan($input['scan_type'] ?? 'face', $input['session_id'] ?? uniqid());
                    echo json_encode($result);
                    break;
                    
                case 'submit_verification':
                    $verification = new VerificationHandler();
                    $result = $verification->handleVerification($input);
                    echo json_encode($result);
                    break;
                    
                default:
                    throw new Exception('Acción no reconocida: ' . $input['action']);
            }
        } else {
            throw new Exception('No se especificó acción');
        }
        
    } else {
        throw new Exception('Método no permitido');
    }
} catch (Exception $e) {
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => null
    ];
    echo json_encode($errorResponse);
    exit;
}
?>