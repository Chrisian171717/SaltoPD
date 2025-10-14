<?php
// Back-end/get_image.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar que los parámetros existen
if (!isset($_GET['path']) || !isset($_GET['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros path y type requeridos']);
    exit;
}

$imagePath = $_GET['path'];
$type = $_GET['type'];

// Sanitizar el path para seguridad
$imagePath = ltrim($imagePath, '/');
$imagePath = str_replace('..', '', $imagePath); // Prevenir directory traversal

// Si es una ruta temporal, generar una imagen de prueba
if (strpos($imagePath, '/temp/') === 0 || strpos($imagePath, 'temp/') === 0) {
    generateMockImage($type, basename($imagePath));
    exit;
}

// Si no existe el archivo, generar uno de prueba
if (!file_exists($imagePath)) {
    generateMockImage($type, basename($imagePath));
    exit;
}

// Servir la imagen real
serveImage($imagePath);

function generateMockImage($type, $filename) {
    // Crear una imagen de prueba basada en el tipo
    if ($type === 'face') {
        createFaceImage();
    } else if ($type === 'document') {
        createDocumentImage();
    } else {
        createDefaultImage();
    }
}

function createFaceImage() {
    // Crear una imagen de rostro de prueba
    $width = 400;
    $height = 400;
    
    $image = imagecreate($width, $height);
    
    // Fondo azul claro
    $background = imagecolorallocate($image, 240, 245, 255);
    imagefill($image, 0, 0, $background);
    
    // Círculo para la cara
    $faceColor = imagecolorallocate($image, 255, 220, 177);
    imagefilledellipse($image, $width/2, $height/2, 300, 350, $faceColor);
    
    // Ojos
    $eyeColor = imagecolorallocate($image, 255, 255, 255);
    $pupilColor = imagecolorallocate($image, 0, 0, 0);
    
    imagefilledellipse($image, $width/2 - 60, $height/2 - 30, 60, 40, $eyeColor);
    imagefilledellipse($image, $width/2 + 60, $height/2 - 30, 60, 40, $eyeColor);
    imagefilledellipse($image, $width/2 - 60, $height/2 - 30, 25, 25, $pupilColor);
    imagefilledellipse($image, $width/2 + 60, $height/2 - 30, 25, 25, $pupilColor);
    
    // Boca
    $mouthColor = imagecolorallocate($image, 255, 150, 150);
    imagefilledarc($image, $width/2, $height/2 + 50, 120, 80, 0, 180, $mouthColor, IMG_ARC_PIE);
    
    // Nariz
    $noseColor = imagecolorallocate($image, 255, 200, 177);
    imagefilledellipse($image, $width/2, $height/2 + 10, 30, 20, $noseColor);
    
    // Texto
    $textColor = imagecolorallocate($image, 100, 100, 100);
    $text = "Rostro Escaneado";
    imagestring($image, 5, $width/2 - 70, $height - 30, $text, $textColor);
    
    // Enviar imagen
    header('Content-Type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image);
    exit;
}

function createDocumentImage() {
    // Crear una imagen de documento de prueba
    $width = 600;
    $height = 400;
    
    $image = imagecreate($width, $height);
    
    // Fondo blanco
    $background = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $background);
    
    // Borde
    $borderColor = imagecolorallocate($image, 200, 200, 200);
    imagerectangle($image, 5, 5, $width-5, $height-5, $borderColor);
    
    // Título
    $titleColor = imagecolorallocate($image, 0, 0, 139);
    $title = "CEDULA DE IDENTIDAD";
    imagestring($image, 4, $width/2 - 100, 20, $title, $titleColor);
    
    // Línea separadora
    imageline($image, 20, 50, $width-20, 50, $borderColor);
    
    // Información del documento
    $textColor = imagecolorallocate($image, 0, 0, 0);
    $info = [
        "Nombre: JUAN PEREZ GONZALEZ",
        "RUT: 12.345.678-9",
        "Nacionalidad: CHILENA",
        "Fecha Nac.: 15-03-1990",
        "N° Documento: CI123456789",
        "Fecha Emisión: 10-01-2023"
    ];
    
    $y = 80;
    foreach ($info as $line) {
        imagestring($image, 3, 30, $y, $line, $textColor);
        $y += 30;
    }
    
    // Sello
    $stampColor = imagecolorallocate($image, 255, 0, 0);
    imagefilledellipse($image, $width - 80, $height - 80, 100, 100, $stampColor);
    $stampTextColor = imagecolorallocate($image, 255, 255, 255);
    imagestring($image, 2, $width - 105, $height - 85, "VALIDO", $stampTextColor);
    
    // Enviar imagen
    header('Content-Type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image);
    exit;
}

function createDefaultImage() {
    // Imagen por defecto
    $width = 400;
    $height = 300;
    
    $image = imagecreate($width, $height);
    
    // Fondo gris
    $background = imagecolorallocate($image, 200, 200, 200);
    imagefill($image, 0, 0, $background);
    
    // Texto
    $textColor = imagecolorallocate($image, 100, 100, 100);
    $text = "Imagen no disponible";
    imagestring($image, 5, $width/2 - 100, $height/2 - 10, $text, $textColor);
    
    header('Content-Type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image);
    exit;
}

function serveImage($imagePath) {
    // Verificar que el archivo existe y es una imagen
    if (!file_exists($imagePath) || !getimagesize($imagePath)) {
        generateMockImage('default', basename($imagePath));
        exit;
    }
    
    // Determinar el tipo MIME
    $mimeType = mime_content_type($imagePath);
    
    // Servir la imagen
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($imagePath));
    readfile($imagePath);
    exit;
}

// Si llega aquí, hay un error
http_response_code(404);
echo json_encode(['error' => 'Imagen no encontrada']);
?>