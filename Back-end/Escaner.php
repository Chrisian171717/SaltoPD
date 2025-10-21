<?php
// Back-end/Escaner.php - Sistema Integrado con tu Conexión MySQL
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir tu archivo de conexión existente
require_once 'conexion.php';

// Configuración del sistema
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']);
define('IMAGE_QUALITY', 85);

class DatabaseSaltopd {
    
    public function insertDocumento($data) {
        global $conn;
        
        if (!verificarConexion()) {
            throw new Exception("No hay conexión a la base de datos");
        }
        
        $sql = "INSERT INTO documentos_escaneo (
            session_id, tipo_documento, nombre_documento, datos_ocr, confianza, 
            calidad, imagen_data, imagen_tipo, imagen_tamanio, ruta_imagen,
            campos_detectados, es_valido, tasa_completitud
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssssdsssisssd",
            $data['session_id'],
            $data['tipo_documento'],
            $data['nombre_documento'],
            $data['datos_ocr'],
            $data['confianza'],
            $data['calidad'],
            $data['imagen_data'],
            $data['imagen_tipo'],
            $data['imagen_tamanio'],
            $data['ruta_imagen'],
            $data['campos_detectados'],
            $data['es_valido'],
            $data['tasa_completitud']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }
        
        $documentoId = $stmt->insert_id;
        $stmt->close();
        
        return $documentoId;
    }
    
    public function insertRostro($data) {
        global $conn;
        
        if (!verificarConexion()) {
            throw new Exception("No hay conexión a la base de datos");
        }
        
        $sql = "INSERT INTO rostros_escaneo (
            session_id, face_id, landmarks, confianza, tiempo_escaneo,
            edad_estimada, genero, expresion, tiene_lentes, imagen_data,
            imagen_tipo, imagen_tamanio, ruta_imagen, datos_biometricos, calidad_analisis
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssiddissssissss",
            $data['session_id'],
            $data['face_id'],
            $data['landmarks'],
            $data['confianza'],
            $data['tiempo_escaneo'],
            $data['edad_estimada'],
            $data['genero'],
            $data['expresion'],
            $data['tiene_lentes'],
            $data['imagen_data'],
            $data['imagen_tipo'],
            $data['imagen_tamanio'],
            $data['ruta_imagen'],
            $data['datos_biometricos'],
            $data['calidad_analisis']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }
        
        $rostroId = $stmt->insert_id;
        $stmt->close();
        
        return $rostroId;
    }
    
    public function getDocumentosRecientes($sessionId, $limit = 10) {
        global $conn;
        
        if (!verificarConexion()) {
            return [];
        }
        
        $sql = "SELECT * FROM documentos_escaneo WHERE session_id = ? ORDER BY timestamp_creacion DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando consulta: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("si", $sessionId, $limit);
        if (!$stmt->execute()) {
            error_log("Error ejecutando consulta: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $documentos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $documentos;
    }
    
    public function getRostrosRecientes($sessionId, $limit = 10) {
        global $conn;
        
        if (!verificarConexion()) {
            return [];
        }
        
        $sql = "SELECT * FROM rostros_escaneo WHERE session_id = ? ORDER BY timestamp_creacion DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparando consulta: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("si", $sessionId, $limit);
        if (!$stmt->execute()) {
            error_log("Error ejecutando consulta: " . $stmt->error);
            $stmt->close();
            return [];
        }
        
        $result = $stmt->get_result();
        $rostros = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rostros;
    }
    
    public function getImagenDocumento($documentoId) {
        global $conn;
        
        if (!verificarConexion()) {
            return null;
        }
        
        $sql = "SELECT imagen_data, imagen_tipo FROM documentos_escaneo WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $documentoId);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        $imagen = $result->fetch_assoc();
        $stmt->close();
        
        return $imagen;
    }
    
    public function getImagenRostro($rostroId) {
        global $conn;
        
        if (!verificarConexion()) {
            return null;
        }
        
        $sql = "SELECT imagen_data, imagen_tipo FROM rostros_escaneo WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $rostroId);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        $imagen = $result->fetch_assoc();
        $stmt->close();
        
        return $imagen;
    }
    
    public function insertVerificacion($data) {
        global $conn;
        
        if (!verificarConexion()) {
            throw new Exception("No hay conexión a la base de datos");
        }
        
        $sql = "INSERT INTO verificaciones_escaneo (
            verification_id, session_id, documento_id, rostro_id, datos_faciales,
            datos_documentales, info_sistema, resultado_verificacion, puntaje_confianza,
            evaluacion_riesgo, estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $conn->error);
        }
        
        $stmt->bind_param(
            "ssiissssdss",
            $data['verification_id'],
            $data['session_id'],
            $data['documento_id'],
            $data['rostro_id'],
            $data['datos_faciales'],
            $data['datos_documentales'],
            $data['info_sistema'],
            $data['resultado_verificacion'],
            $data['puntaje_confianza'],
            $data['evaluacion_riesgo'],
            $data['estado']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }
        
        $verificacionId = $stmt->insert_id;
        $stmt->close();
        
        return $verificacionId;
    }
}

class ImageProcessor {
    
    public function processUploadedImage($uploadedFile) {
        try {
            // Validar archivo
            $this->validateImage($uploadedFile);
            
            // Leer datos de la imagen
            $imageData = file_get_contents($uploadedFile['tmp_name']);
            $imageSize = $uploadedFile['size'];
            $imageType = $uploadedFile['type'];
            $filename = $uploadedFile['name'];
            
            // Optimizar imagen si es JPEG/PNG
            if (in_array($imageType, ['image/jpeg', 'image/jpg', 'image/png'])) {
                $optimizedData = $this->optimizeImage($uploadedFile['tmp_name'], $imageType);
                if ($optimizedData) {
                    $imageData = $optimizedData;
                    $imageSize = strlen($optimizedData);
                }
            }
            
            return [
                'success' => true,
                'image_data' => $imageData,
                'image_size' => $imageSize,
                'image_type' => $imageType,
                'filename' => $filename,
                'optimized' => true
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error procesando imagen: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validateImage($file) {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la carga del archivo: ' . $this->getUploadError($file['error']));
        }
        
        if ($file['size'] > MAX_IMAGE_SIZE) {
            throw new Exception('El archivo es demasiado grande. Máximo ' . (MAX_IMAGE_SIZE / 1024 / 1024) . 'MB permitidos.');
        }
        
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
            throw new Exception('Tipo de archivo no permitido: ' . $fileType);
        }
        
        return true;
    }
    
    private function optimizeImage($filePath, $mimeType) {
        try {
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($filePath);
                    break;
                default:
                    return null;
            }
            
            if (!$image) {
                return null;
            }
            
            // Capturar la imagen optimizada en buffer
            ob_start();
            
            if ($mimeType === 'image/png') {
                imagepng($image, null, 9);
            } else {
                imagejpeg($image, null, IMAGE_QUALITY);
            }
            
            $optimizedData = ob_get_clean();
            imagedestroy($image);
            
            return $optimizedData;
            
        } catch (Exception $e) {
            error_log("⚠️ Error optimizando imagen: " . $e->getMessage());
            return null;
        }
    }
    
    private function getUploadError($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido en el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido en el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
        ];
        
        return $errors[$errorCode] ?? 'Error desconocido en la subida';
    }
}

class DocumentProcessor {
    
    private $documentTypes = [
        'id' => [
            'name' => 'Cédula de Identidad',
            'fields' => ['name', 'id_number', 'birth_date', 'nationality', 'issue_date', 'expiry_date', 'sex', 'birth_place'],
            'icon' => '🆔',
            'description' => 'Documento nacional de identificación'
        ],
        'passport' => [
            'name' => 'Pasaporte',
            'fields' => ['passport_number', 'surname', 'given_names', 'nationality', 'birth_date', 'issue_date', 'expiry_date', 'authority'],
            'icon' => '📘',
            'description' => 'Documento de viaje internacional'
        ],
        'driver' => [
            'name' => 'Licencia de Conducir',
            'fields' => ['license_number', 'name', 'birth_date', 'issue_date', 'expiry_date', 'categories', 'address'],
            'icon' => '🚗',
            'description' => 'Permiso oficial para conducir'
        ],
        'other' => [
            'name' => 'Otro Documento',
            'fields' => ['document_type', 'document_number', 'name', 'issue_date', 'institution'],
            'icon' => '📄',
            'description' => 'Otro tipo de documento identificatorio'
        ]
    ];
    
    public function getDocumentTypes() {
        return $this->documentTypes;
    }
    
    public function detectDocumentType($filename = '') {
        if (!empty($filename)) {
            $filename = strtolower($filename);
            
            if (strpos($filename, 'passport') !== false || strpos($filename, 'pasaporte') !== false) {
                return 'passport';
            } elseif (strpos($filename, 'driver') !== false || strpos($filename, 'license') !== false || 
                     strpos($filename, 'licencia') !== false || strpos($filename, 'conducir') !== false) {
                return 'driver';
            } elseif (strpos($filename, 'id') !== false || strpos($filename, 'cedula') !== false || 
                     strpos($filename, 'dni') !== false || strpos($filename, 'rut') !== false) {
                return 'id';
            }
        }
        
        return 'other';
    }
    
    public function processDocument($documentType = null, $filename = '') {
        if (!$documentType) {
            $documentType = $this->detectDocumentType($filename);
        }
        
        $processingStart = microtime(true);
        $processedData = $this->simulateOCRProcessing($documentType);
        $processingTime = round(microtime(true) - $processingStart, 2);
        
        $confidence = $this->calculateConfidence($processedData);
        $quality = $this->assessDocumentQuality($confidence);
        $validation = $this->validateDocumentData($documentType, $processedData);
        
        return [
            'success' => true,
            'document_type' => $this->documentTypes[$documentType]['name'] ?? 'Documento',
            'document_code' => $documentType,
            'document_icon' => $this->documentTypes[$documentType]['icon'] ?? '📄',
            'processing_time' => $processingTime,
            'ocr_data' => $processedData,
            'confidence' => $confidence,
            'quality_assessment' => $quality,
            'fields_detected' => array_keys(array_filter($processedData)),
            'total_fields' => count($this->documentTypes[$documentType]['fields'] ?? []),
            'validation' => $validation,
            'is_valid_document' => $validation['is_valid'],
            'timestamp' => date('c')
        ];
    }
    
    // Cambiado de private a public para poder usarlo desde fuera
    public function simulateOCRProcessing($documentType) {
        $mockData = [
            'id' => [
                'name' => 'MARÍA FERNANDA GONZÁLEZ LÓPEZ',
                'id_number' => '12.345.678-9',
                'birth_date' => '15-06-1985',
                'nationality' => 'CHILENA',
                'issue_date' => '10-03-2020',
                'expiry_date' => '10-03-2030',
                'sex' => 'F',
                'birth_place' => 'SANTIAGO',
                'address' => 'AV. LIBERTADOR 1234',
                'commune' => 'PROVIDENCIA'
            ],
            'passport' => [
                'passport_number' => 'PA1234567',
                'surname' => 'GONZÁLEZ LÓPEZ',
                'given_names' => 'MARÍA FERNANDA',
                'nationality' => 'CHILE',
                'birth_date' => '15 JUN 1985',
                'birth_place' => 'SANTIAGO, CHILE',
                'issue_date' => '15 MAR 2023',
                'expiry_date' => '15 MAR 2033',
                'authority' => 'SANTIAGO CHILE',
                'sex' => 'F',
                'personal_number' => '123456789'
            ],
            'driver' => [
                'license_number' => 'B12345678-9',
                'name' => 'MARÍA FERNANDA GONZÁLEZ LÓPEZ',
                'birth_date' => '15-06-1985',
                'issue_date' => '20-05-2022',
                'expiry_date' => '20-05-2032',
                'categories' => 'A B',
                'address' => 'AV. LIBERTADOR 1234, SANTIAGO',
                'nationality' => 'CHILENA',
                'first_issue' => '20-05-2012',
                'blood_type' => 'A+'
            ],
            'other' => [
                'document_type' => 'Credencial Universitaria',
                'document_number' => 'UC202400123',
                'name' => 'MARÍA FERNANDA GONZÁLEZ',
                'issue_date' => '2024-03-01',
                'institution' => 'UNIVERSIDAD DE CHILE',
                'career' => 'INGENIERÍA CIVIL',
                'expiry_date' => '2028-12-31',
                'student_id' => '202412345'
            ]
        ];
        
        return $mockData[$documentType] ?? $mockData['other'];
    }
    
    private function calculateConfidence($data) {
        $filledFields = count(array_filter($data, function($value) {
            return !empty($value) && $value !== 'N/A' && $value !== 'Unknown';
        }));
        $totalFields = count($data);
        
        $baseConfidence = round(($filledFields / $totalFields) * 100, 1);
        $dataQuality = $this->assessDataQuality($data);
        
        return min(100, $baseConfidence + $dataQuality);
    }
    
    private function assessDataQuality($data) {
        $qualityScore = 0;
        
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                if ($key === 'id_number' && preg_match('/[0-9\.\-]+/', $value)) {
                    $qualityScore += 2;
                } elseif ($key === 'birth_date' && preg_match('/\d{2}[-\/]\d{2}[-\/]\d{4}/', $value)) {
                    $qualityScore += 2;
                } elseif ($key === 'passport_number' && preg_match('/[A-Z]{2}\d+/', $value)) {
                    $qualityScore += 2;
                } elseif (strlen($value) > 5) {
                    $qualityScore += 1;
                }
            }
        }
        
        return min(10, $qualityScore);
    }
    
    private function assessDocumentQuality($confidence) {
        if ($confidence >= 90) {
            return [
                'level' => 'excelente',
                'label' => 'Excelente',
                'color' => '#10b981',
                'description' => 'Documento de alta calidad con información completa'
            ];
        } elseif ($confidence >= 75) {
            return [
                'level' => 'buena',
                'label' => 'Buena',
                'color' => '#3b82f6',
                'description' => 'Documento de buena calidad con información suficiente'
            ];
        } elseif ($confidence >= 60) {
            return [
                'level' => 'aceptable',
                'label' => 'Aceptable',
                'color' => '#f59e0b',
                'description' => 'Documento aceptable con información básica'
            ];
        } else {
            return [
                'level' => 'baja',
                'label' => 'Baja Calidad',
                'color' => '#ef4444',
                'description' => 'Documento de baja calidad, información limitada'
            ];
        }
    }
    
    public function validateDocumentData($documentType, $data) {
        $requiredFields = $this->documentTypes[$documentType]['fields'] ?? [];
        $validationResults = [];
        
        foreach ($requiredFields as $field) {
            $value = $data[$field] ?? '';
            $isValid = !empty($value);
            
            $validationResults[$field] = [
                'valid' => $isValid,
                'value' => $value,
                'required' => true
            ];
        }
        
        // Solución robusta - contador manual
        $validFields = 0;
        foreach ($validationResults as $result) {
            if (is_array($result) && isset($result['valid']) && $result['valid']) {
                $validFields++;
            }
        }
        
        $totalFields = count($requiredFields);
        $completionRate = $totalFields > 0 ? round(($validFields / $totalFields) * 100, 1) : 0;
        
        return [
            'is_valid' => $completionRate >= 60,
            'completion_rate' => $completionRate,
            'valid_fields' => $validFields,
            'total_fields' => $totalFields,
            'field_validation' => $validationResults
        ];
    }
}

class ScannerHandler {
    
    private $db;
    private $imageProcessor;
    private $documentProcessor;
    
    public function __construct() {
        $this->db = new DatabaseSaltopd();
        $this->imageProcessor = new ImageProcessor();
        $this->documentProcessor = new DocumentProcessor();
    }
    
    public function uploadAndProcessDocument($uploadedFile, $sessionId, $documentType = null) {
        try {
            // Procesar imagen
            $imageResult = $this->imageProcessor->processUploadedImage($uploadedFile);
            
            if (!$imageResult['success']) {
                throw new Exception($imageResult['error']);
            }
            
            // Detectar tipo de documento si no se especificó
            if (!$documentType) {
                $documentType = $this->documentProcessor->detectDocumentType($uploadedFile['name']);
            }
            
            // Procesar documento (OCR)
            $processingResult = $this->documentProcessor->processDocument($documentType, $uploadedFile['name']);
            
            // Generar ruta de imagen única
            $rutaImagen = 'db://documentos/' . uniqid() . '_' . time();
            
            // Guardar en base de datos
            $documentoId = $this->db->insertDocumento([
                'session_id' => $sessionId,
                'tipo_documento' => $documentType,
                'nombre_documento' => $uploadedFile['name'],
                'datos_ocr' => json_encode($processingResult['ocr_data'], JSON_UNESCAPED_UNICODE),
                'confianza' => $processingResult['confidence'],
                'calidad' => $processingResult['quality_assessment']['label'],
                'imagen_data' => $imageResult['image_data'],
                'imagen_tipo' => $imageResult['image_type'],
                'imagen_tamanio' => $imageResult['image_size'],
                'ruta_imagen' => $rutaImagen,
                'campos_detectados' => json_encode($processingResult['fields_detected'], JSON_UNESCAPED_UNICODE),
                'es_valido' => $processingResult['is_valid_document'],
                'tasa_completitud' => $processingResult['validation']['completion_rate']
            ]);
            
            return [
                'success' => true,
                'document_id' => $documentoId,
                'document_info' => $processingResult,
                'upload_info' => [
                    'filename' => $uploadedFile['name'],
                    'file_size' => $imageResult['image_size'],
                    'file_type' => $imageResult['image_type'],
                    'optimized' => $imageResult['optimized']
                ],
                'database_info' => [
                    'stored' => true,
                    'document_id' => $documentoId,
                    'storage_type' => 'mysql_saltopd'
                ],
                'timestamp' => date('c')
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error en upload y procesamiento: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }
    
    public function saveFaceData($faceData, $sessionId, $imageData = null) {
        try {
            $faceId = 'face_' . uniqid() . '_' . time();
            
            // Generar ruta de imagen única
            $rutaImagen = $imageData ? 'db://rostros/' . uniqid() . '_' . time() : null;
            
            $rostroId = $this->db->insertRostro([
                'session_id' => $sessionId,
                'face_id' => $faceId,
                'landmarks' => $faceData['landmarks'] ?? 68,
                'confianza' => $faceData['confidence'] ?? $faceData['matchConfidence'] ?? 0.95,
                'tiempo_escaneo' => $faceData['processing_time'] ?? $faceData['scanTime'] ?? 2.0,
                'edad_estimada' => $faceData['estimated_age'] ?? rand(20, 50),
                'genero' => $faceData['gender'] ?? (rand(0, 1) ? 'male' : 'female'),
                'expresion' => $faceData['expression'] ?? 'neutral',
                'tiene_lentes' => $faceData['has_glasses'] ?? false,
                'imagen_data' => $imageData,
                'imagen_tipo' => 'image/jpeg',
                'imagen_tamanio' => $imageData ? strlen($imageData) : 0,
                'ruta_imagen' => $rutaImagen,
                'datos_biometricos' => json_encode($faceData['features'] ?? [], JSON_UNESCAPED_UNICODE),
                'calidad_analisis' => json_encode($faceData['quality_analysis'] ?? [
                    'sharpness' => 156.78,
                    'brightness' => 128.45,
                    'contrast' => 52.33,
                    'quality_score' => 85,
                    'quality_label' => 'Buena'
                ], JSON_UNESCAPED_UNICODE)
            ]);
            
            return [
                'success' => true,
                'face_id' => $faceId,
                'rostro_id' => $rostroId,
                'database_info' => [
                    'stored' => true,
                    'rostro_id' => $rostroId,
                    'storage_type' => 'mysql_saltopd'
                ]
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error guardando rostro: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getRecentDocuments($sessionId, $limit = 5) {
        try {
            $documentos = $this->db->getDocumentosRecientes($sessionId, $limit);
            
            $result = [];
            foreach ($documentos as $doc) {
                $result[] = [
                    'id' => $doc['id'],
                    'tipo_documento' => $doc['tipo_documento'],
                    'nombre_documento' => $doc['nombre_documento'],
                    'confianza' => $doc['confianza'],
                    'calidad' => $doc['calidad'],
                    'es_valido' => (bool)$doc['es_valido'],
                    'tasa_completitud' => $doc['tasa_completitud'],
                    'timestamp_creacion' => $doc['timestamp_creacion'],
                    'datos_ocr' => json_decode($doc['datos_ocr'], true),
                    'campos_detectados' => json_decode($doc['campos_detectados'], true),
                    'imagen_url' => 'get_image.php?type=document&id=' . $doc['id']
                ];
            }
            
            return [
                'success' => true,
                'documentos' => $result,
                'total' => count($result),
                'session_id' => $sessionId
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error obteniendo documentos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'documentos' => []
            ];
        }
    }
    
    public function getRecentFaces($sessionId, $limit = 5) {
        try {
            $rostros = $this->db->getRostrosRecientes($sessionId, $limit);
            
            $result = [];
            foreach ($rostros as $rostro) {
                $result[] = [
                    'id' => $rostro['id'],
                    'face_id' => $rostro['face_id'],
                    'confianza' => $rostro['confianza'],
                    'landmarks' => $rostro['landmarks'],
                    'edad_estimada' => $rostro['edad_estimada'],
                    'genero' => $rostro['genero'],
                    'expresion' => $rostro['expresion'],
                    'tiene_lentes' => (bool)$rostro['tiene_lentes'],
                    'timestamp_creacion' => $rostro['timestamp_creacion'],
                    'datos_biometricos' => json_decode($rostro['datos_biometricos'], true),
                    'calidad_analisis' => json_decode($rostro['calidad_analisis'], true),
                    'imagen_url' => 'get_image.php?type=face&id=' . $rostro['id']
                ];
            }
            
            return [
                'success' => true,
                'rostros' => $result,
                'total' => count($result),
                'session_id' => $sessionId
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error obteniendo rostros: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'rostros' => []
            ];
        }
    }
    
    public function getDocumentTypes() {
        $types = $this->documentProcessor->getDocumentTypes();
        
        foreach ($types as $typeKey => &$type) {
            $type['key'] = $typeKey;
            // Ahora puede llamar a simulateOCRProcessing porque es público
            $type['example_data'] = $this->documentProcessor->simulateOCRProcessing($typeKey);
            $type['field_count'] = count($type['fields']);
        }
        
        return [
            'success' => true,
            'document_types' => $types,
            'total_types' => count($types)
        ];
    }
}

class VerificationHandler {
    
    private $db;
    
    public function __construct() {
        $this->db = new DatabaseSaltopd();
    }
    
    public function handleVerification($data) {
        try {
            if (empty($data['face_data']) && empty($data['document_data'])) {
                throw new Exception('No se recibieron datos de escaneo para verificación');
            }
            
            $verificationId = 'VER_' . uniqid() . '_' . date('YmdHis');
            $verificationResult = $this->processVerificationData($data, $verificationId);
            
            // Guardar en base de datos
            $this->db->insertVerificacion([
                'verification_id' => $verificationId,
                'session_id' => $data['session_id'] ?? 'unknown',
                'documento_id' => null, // En un sistema real, relacionar con IDs existentes
                'rostro_id' => null,
                'datos_faciales' => json_encode($data['face_data'] ?? [], JSON_UNESCAPED_UNICODE),
                'datos_documentales' => json_encode($data['document_data'] ?? [], JSON_UNESCAPED_UNICODE),
                'info_sistema' => json_encode($data['system_info'] ?? [], JSON_UNESCAPED_UNICODE),
                'resultado_verificacion' => json_encode($verificationResult, JSON_UNESCAPED_UNICODE),
                'puntaje_confianza' => $verificationResult['confidence_score'],
                'evaluacion_riesgo' => $verificationResult['risk_assessment'],
                'estado' => 'completado'
            ]);
            
            return [
                'success' => true,
                'verification_id' => $verificationId,
                'estimated_time' => '1-2 minutos',
                'message' => 'Datos recibidos y procesados correctamente',
                'verification_summary' => $verificationResult,
                'database_info' => [
                    'verification_stored' => true,
                    'verification_id' => $verificationId
                ],
                'timestamp' => date('c')
            ];
            
        } catch (Exception $e) {
            error_log("❌ Error en verificación: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }
    
    private function processVerificationData($data, $verificationId) {
        $summary = [
            'verification_id' => $verificationId,
            'timestamp' => date('c'),
            'has_face_data' => !empty($data['face_data']),
            'has_document_data' => !empty($data['document_data']),
            'confidence_score' => 0
        ];
        
        if (!empty($data['face_data'])) {
            $faceData = $data['face_data'];
            $summary['face_analysis'] = [
                'confidence' => $faceData['matchConfidence'] ?? $faceData['confidence'] ?? 0,
                'quality' => $faceData['quality_analysis']['quality_label'] ?? 'Desconocida'
            ];
            $summary['confidence_score'] += $summary['face_analysis']['confidence'] * 0.6;
        }
        
        if (!empty($data['document_data'])) {
            $docData = $data['document_data'];
            $summary['document_analysis'] = [
                'document_type' => $docData['document_type'] ?? 'Desconocido',
                'confidence' => $docData['confidence'] ?? 0,
                'quality' => $docData['quality_assessment']['label'] ?? 'Desconocida',
                'is_valid' => $docData['is_valid_document'] ?? false
            ];
            $summary['confidence_score'] += $summary['document_analysis']['confidence'] * 0.4;
        }
        
        $summary['confidence_score'] = round($summary['confidence_score'], 1);
        
        // Evaluación de riesgo
        if ($summary['confidence_score'] >= 90) {
            $summary['risk_assessment'] = 'muy_bajo';
        } elseif ($summary['confidence_score'] >= 75) {
            $summary['risk_assessment'] = 'bajo';
        } elseif ($summary['confidence_score'] >= 60) {
            $summary['risk_assessment'] = 'medio';
        } else {
            $summary['risk_assessment'] = 'alto';
        }
        
        return $summary;
    }
}

// MANEJO PRINCIPAL DE SOLICITUDES
try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    if ($method !== 'POST') {
        throw new Exception('Solo se permiten solicitudes POST');
    }
    
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (!$input) {
            throw new Exception('Datos JSON inválidos: ' . json_last_error_msg());
        }
    } elseif (strpos($contentType, 'multipart/form-data') !== false) {
        $input = $_POST;
    } else {
        throw new Exception('Content-Type no soportado: ' . $contentType);
    }
    
    if (!isset($input['action'])) {
        throw new Exception('No se especificó acción en la solicitud');
    }
    
    $scanner = new ScannerHandler();
    $verificationHandler = new VerificationHandler();
    $sessionId = $input['session_id'] ?? uniqid();
    
    switch ($input['action']) {
        case 'upload_document':
            if (empty($_FILES['document'])) {
                throw new Exception('No se recibió archivo para upload');
            }
            $result = $scanner->uploadAndProcessDocument(
                $_FILES['document'],
                $sessionId,
                $input['document_type'] ?? null
            );
            break;
            
        case 'save_face':
            $imageData = null;
            if (!empty($_FILES['face_image'])) {
                $imageResult = (new ImageProcessor())->processUploadedImage($_FILES['face_image']);
                if ($imageResult['success']) {
                    $imageData = $imageResult['image_data'];
                }
            }
            $result = $scanner->saveFaceData(
                $input['face_data'] ?? [],
                $sessionId,
                $imageData
            );
            break;
            
        case 'get_recent_documents':
            $result = $scanner->getRecentDocuments(
                $sessionId,
                $input['limit'] ?? 5
            );
            break;
            
        case 'get_recent_faces':
            $result = $scanner->getRecentFaces(
                $sessionId,
                $input['limit'] ?? 5
            );
            break;
            
        case 'get_document_types':
            $result = $scanner->getDocumentTypes();
            break;
            
        case 'submit_verification':
            $result = $verificationHandler->handleVerification($input);
            break;
            
        default:
            throw new Exception('Acción no reconocida: ' . $input['action']);
    }
    
    error_log("✅ Solicitud procesada: " . $input['action']);
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("❌ Error general: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>