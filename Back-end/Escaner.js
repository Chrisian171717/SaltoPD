// ===== VARIABLES GLOBALES =====
let systemState;
let elements;
let cameraStream = null;

// ===== INICIALIZACIÓN DEL SISTEMA =====
function initializeSystem() {
    console.log('Sistema de escaneo inicializado');
    
    // Inicializar estado del sistema
    systemState = {
        cameraActive: false,
        scanning: false,
        currentFaceData: null,
        currentDocumentData: null
    };
    
    // Inicializar elementos UI
    elements = {
        // Elementos de escáner facial
        scannerStatus: document.getElementById('scanner-status'),
        accuracy: document.getElementById('accuracy'),
        landmarks: document.getElementById('landmarks'),
        scanTime: document.getElementById('scan-time'),
        matchConfidence: document.getElementById('match-confidence'),
        
        // Elementos de documento
        docType: document.getElementById('doc-type'),
        quality: document.getElementById('quality'),
        ocrStatus: document.getElementById('ocr-status'),
        extractedInfo: document.getElementById('extracted-info'),
        
        // Botones de control
        submitData: document.getElementById('submit-data'),
        startScan: document.getElementById('start-scan'),
        stopScan: document.getElementById('stop-scan'),
        
        // Elementos visuales
        faceCanvas: document.getElementById('face-canvas'),
        cameraFeed: document.getElementById('camera-feed'),
        cameraToggle: document.getElementById('camera-toggle'),
        
        // Elementos de resultados
        facialMatch: document.getElementById('facial-match'),
        facialMatchBar: document.getElementById('facial-match-bar'),
        docVerification: document.getElementById('doc-verification'),
        overallStatus: document.getElementById('overall-status'),
        
        // Elementos de estado
        cameraStatusText: document.getElementById('camera-status-text'),
        processingStatusText: document.getElementById('processing-status-text'),
        verificationStatusText: document.getElementById('verification-status-text'),
        alertStatusText: document.getElementById('alert-status-text')
    };
    
    updateSystemStatus('Sistema listo', 'ready');
    updateStatusIndicators();
}

// ===== FUNCIONES AUXILIARES =====
function generateSessionId() {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function updateSystemStatus(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    // Actualizar estado de procesamiento
    if (elements.processingStatusText) {
        elements.processingStatusText.textContent = message;
    }
    
    // Actualizar alertas si es error
    if (type === 'error' && elements.alertStatusText) {
        elements.alertStatusText.textContent = message;
    }
}

function updateStatusIndicators() {
    // Actualizar estado de la cámara
    if (elements.cameraStatusText) {
        elements.cameraStatusText.textContent = systemState.cameraActive ? 'Activa' : 'Inactiva';
    }
    
    // Actualizar estado de verificación
    if (elements.verificationStatusText) {
        const hasData = systemState.currentFaceData || systemState.currentDocumentData;
        elements.verificationStatusText.textContent = hasData ? 'Datos listos' : 'Pendiente';
    }
}

// ===== CONTROL DE CÁMARA =====
async function toggleCamera() {
    if (cameraStream) {
        // Apagar cámara
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
        systemState.cameraActive = false;
        
        if (elements.cameraFeed) {
            elements.cameraFeed.srcObject = null;
        }
        if (elements.cameraToggle) {
            elements.cameraToggle.textContent = 'Activar';
        }
        
        updateSystemStatus('Cámara desactivada', 'info');
        return false;
    } else {
        // Encender cámara
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: 640, 
                    height: 480,
                    facingMode: 'user'
                } 
            });
            systemState.cameraActive = true;
            
            if (elements.cameraFeed) {
                elements.cameraFeed.srcObject = cameraStream;
            }
            if (elements.cameraToggle) {
                elements.cameraToggle.textContent = 'Desactivar';
            }
            
            // Habilitar botón de inicio de escaneo
            if (elements.startScan) {
                elements.startScan.disabled = false;
            }
            
            updateSystemStatus('Cámara activada', 'success');
            return true;
        } catch (error) {
            console.error('Error al acceder a la cámara:', error);
            showModal('Error de Cámara', `No se pudo acceder a la cámara: ${error.message}`);
            updateSystemStatus('Error al activar cámara', 'error');
            return false;
        }
    }
}

// ===== FUNCIONES DE ESCANEO =====
function startScan() {
    if (!systemState.cameraActive) {
        // Si no hay cámara web activa, usar Python
        showModal('Seleccionar Escáner', 
            '¿Desea usar la cámara web o el escáner Python externo?', 
            [
                { text: 'Cámara Web', action: () => toggleCamera().then(() => startWebScan()) },
                { text: 'Escáner Python', action: () => executePythonScan('face') },
                { text: 'Cancelar', action: () => {} }
            ]
        );
        return;
    }
    startWebScan();
}

function startWebScan() {
    systemState.scanning = true;
    if (elements.scannerStatus) {
        elements.scannerStatus.textContent = 'Escaneando...';
    }
    if (elements.startScan) {
        elements.startScan.disabled = true;
    }
    if (elements.stopScan) {
        elements.stopScan.disabled = false;
    }
    
    updateSystemStatus('Iniciando escaneo facial', 'processing');
    simulateFacialScan();
    updateStatusIndicators();
}

function stopScan() {
    systemState.scanning = false;
    if (elements.scannerStatus) {
        elements.scannerStatus.textContent = 'Detenido';
    }
    if (elements.startScan) {
        elements.startScan.disabled = false;
    }
    if (elements.stopScan) {
        elements.stopScan.disabled = true;
    }
    updateSystemStatus('Escaneo detenido', 'warning');
    updateStatusIndicators();
}

function simulateFacialScan() {
    console.log('Simulando escaneo facial...');
    
    // Simular proceso de escaneo
    setTimeout(() => {
        if (systemState.scanning) {
            systemState.scanning = false;
            
            // Actualizar UI
            if (elements.scannerStatus) {
                elements.scannerStatus.textContent = 'Completado';
            }
            if (elements.startScan) {
                elements.startScan.disabled = false;
            }
            if (elements.stopScan) {
                elements.stopScan.disabled = true;
            }
            
            // Datos de ejemplo
            systemState.currentFaceData = {
                landmarks: 68,
                accuracy: (Math.random() * 10 + 90).toFixed(1),
                scanTime: (Math.random() * 2 + 1).toFixed(1),
                matchConfidence: (Math.random() * 20 + 80).toFixed(1),
                features: {
                    faceId: `web_face_${Date.now()}`,
                    confidence: 0.95
                }
            };
            
            // Actualizar elementos de datos biométricos
            if (elements.accuracy) {
                elements.accuracy.textContent = systemState.currentFaceData.accuracy + '%';
            }
            if (elements.landmarks) {
                elements.landmarks.textContent = systemState.currentFaceData.landmarks;
            }
            if (elements.scanTime) {
                elements.scanTime.textContent = systemState.currentFaceData.scanTime + 's';
            }
            if (elements.matchConfidence) {
                elements.matchConfidence.textContent = systemState.currentFaceData.matchConfidence + '%';
            }
            
            // Generar imagen local para el rostro
            generateLocalImage('face');
            
            // Habilitar envío de datos
            if (elements.submitData) {
                elements.submitData.disabled = false;
            }
            
            updateSystemStatus('Escaneo facial completado', 'success');
            updateStatusIndicators();
            
            // Mostrar resultados de comparación
            showComparisonResults();
        }
    }, 3000);
}

function showComparisonResults() {
    const resultsSection = document.getElementById('results-section');
    if (resultsSection) {
        resultsSection.style.display = 'block';
    }
    
    // Actualizar porcentaje de coincidencia
    if (elements.facialMatch && systemState.currentFaceData) {
        elements.facialMatch.textContent = systemState.currentFaceData.matchConfidence + '%';
        elements.facialMatchBar.style.width = systemState.currentFaceData.matchConfidence + '%';
    }
    
    // Actualizar estado del documento
    if (elements.docVerification && systemState.currentDocumentData) {
        elements.docVerification.textContent = 'Verificado';
        elements.docVerification.className = 'verification-status verified';
    }
    
    // Actualizar estado general
    if (elements.overallStatus) {
        if (systemState.currentFaceData && systemState.currentDocumentData) {
            elements.overallStatus.textContent = 'Completado';
            elements.overallStatus.className = 'overall-status completed';
        } else if (systemState.currentFaceData || systemState.currentDocumentData) {
            elements.overallStatus.textContent = 'Parcial';
            elements.overallStatus.className = 'overall-status partial';
        } else {
            elements.overallStatus.textContent = 'En Proceso';
            elements.overallStatus.className = 'overall-status processing';
        }
    }
}

// ===== MANEJO DE ARCHIVOS =====
function handleImageUpload(event) {
    const file = event.target.files[0];
    if (file) {
        updateSystemStatus('Imagen cargada: ' + file.name, 'info');
        
        // Crear URL temporal para la imagen
        const imageUrl = URL.createObjectURL(file);
        
        // Mostrar imagen en el canvas
        const canvas = elements.faceCanvas;
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                // Procesar la imagen (simular escaneo)
                processUploadedImage();
            };
            
            img.src = imageUrl;
        }
        
        showModal('Imagen Cargada', `Imagen ${file.name} cargada correctamente. Procesando...`);
    }
}

function processUploadedImage() {
    updateSystemStatus('Procesando imagen cargada...', 'processing');
    
    // Simular procesamiento
    setTimeout(() => {
        systemState.currentFaceData = {
            landmarks: 72,
            accuracy: '96.5%',
            scanTime: '1.8s',
            matchConfidence: '94.2%',
            features: {
                faceId: `upload_face_${Date.now()}`,
                confidence: 0.965
            }
        };
        
        // Actualizar UI
        if (elements.accuracy) elements.accuracy.textContent = systemState.currentFaceData.accuracy;
        if (elements.landmarks) elements.landmarks.textContent = systemState.currentFaceData.landmarks;
        if (elements.scanTime) elements.scanTime.textContent = systemState.currentFaceData.scanTime;
        if (elements.matchConfidence) elements.matchConfidence.textContent = systemState.currentFaceData.matchConfidence;
        if (elements.scannerStatus) elements.scannerStatus.textContent = 'Completado';
        if (elements.submitData) elements.submitData.disabled = false;
        
        updateSystemStatus('Imagen procesada correctamente', 'success');
        showComparisonResults();
        updateStatusIndicators();
    }, 2000);
}

function handleDocumentUpload(event) {
    const file = event.target.files[0];
    if (file) {
        updateSystemStatus('Documento cargado: ' + file.name, 'info');
        
        // Crear URL temporal para la imagen del documento
        const imageUrl = URL.createObjectURL(file);
        
        // Mostrar imagen en el preview del documento
        const docPreview = document.getElementById('document-preview');
        if (docPreview) {
            docPreview.style.backgroundImage = `url(${imageUrl})`;
            docPreview.style.backgroundSize = 'cover';
            docPreview.style.backgroundPosition = 'center';
            docPreview.innerHTML = '';
        }
        
        // Ejecutar escaneo Python para documento
        executePythonScan('id');
    }
}

// ===== INTEGRACIÓN CON PYTHON =====
async function executePythonScan(scanType) {
    updateSystemStatus(`Iniciando escaneo ${scanType === 'face' ? 'facial' : 'de documento'}...`, 'processing');
    
    try {
        console.log('Enviando solicitud a PHP...');
        const response = await fetch('../Back-end/Escaner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'execute_python_scan',
                scan_type: scanType,
                session_id: generateSessionId(),
                timestamp: new Date().toISOString()
            })
        });

        console.log('Respuesta recibida, status:', response.status);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }

        const responseText = await response.text();
        console.log('Respuesta texto:', responseText);
        
        if (!responseText) {
            throw new Error('El servidor devolvió una respuesta vacía');
        }

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('Error parseando JSON:', jsonError);
            throw new Error(`Respuesta JSON inválida: ${responseText.substring(0, 100)}...`);
        }

        console.log('Resultado parseado:', result);

        if (result.success) {
            if (result.data && result.data.scan_completed) {
                // Mostrar advertencia si son datos de prueba
                if (result.data.is_mock_data) {
                    showModal('Modo de Prueba', 
                        'El sistema está usando datos de prueba. Para usar el escáner Python real, asegúrate de que Python esté instalado y configurado correctamente.',
                        [{ text: 'Entendido', action: () => {} }]
                    );
                }
                
                await handlePythonScanResult(result.data, scanType);
                updateSystemStatus(`Escaneo ${scanType} completado`, 'success');
            } else {
                throw new Error('El escaneo Python no se completó correctamente');
            }
        } else {
            throw new Error(result.error || 'Error en el escaneo Python');
        }

    } catch (error) {
        console.error('Error en escaneo Python:', error);
        
        // Ofrecer opciones al usuario
        showModal('Error en Escáner', 
            `No se pudo completar el escaneo: ${error.message}. ¿Qué desea hacer?`, 
            [
                { 
                    text: 'Usar Datos de Prueba', 
                    action: () => useMockData(scanType) 
                },
                { 
                    text: 'Usar Cámara Web', 
                    action: () => {
                        if (!systemState.cameraActive && scanType === 'face') {
                            toggleCamera().then(() => startWebScan());
                        } else if (scanType === 'face') {
                            startWebScan();
                        } else {
                            useMockData(scanType);
                        }
                    }
                },
                { 
                    text: 'Cancelar', 
                    action: () => updateSystemStatus('Escaneo cancelado', 'warning') 
                }
            ]
        );
    }
}

// Función para usar datos de prueba
function useMockData(scanType) {
    const mockData = scanType === 'face' ? 
    {
        scan_completed: true,
        landmarks: 68,
        confidence: 0.95,
        processing_time: 2.3,
        face_id: `mock_face_${Date.now()}`,
        estimated_age: 30,
        gender: 'male',
        expression: 'neutral',
        has_glasses: false,
        image_path: null,
        is_mock_data: true
    } : 
    {
        scan_completed: true,
        quality: 'Buena',
        extraction_success: true,
        ocr_data: {
            name: 'Usuario de Prueba',
            idNumber: '12345678-9',
            birthDate: '1990-01-01',
            nationality: 'Chilena'
        },
        processing_time: 1.8,
        image_path: null,
        is_mock_data: true
    };
    
    handlePythonScanResult(mockData, scanType);
    updateSystemStatus(`Escaneo ${scanType} completado (modo prueba)`, 'success');
}

async function handlePythonScanResult(result, scanType) {
    if (scanType === 'face') {
        // Procesar resultado de escaneo facial
        systemState.currentFaceData = {
            landmarks: result.landmarks || 68,
            accuracy: result.confidence ? (result.confidence * 100).toFixed(1) : 95.0,
            scanTime: result.processing_time || 2.5,
            matchConfidence: result.confidence ? (result.confidence * 100).toFixed(1) : 95.0,
            features: {
                faceId: result.face_id || `face_${Date.now()}`,
                confidence: result.confidence || 0.95,
                boundingBox: result.bounding_box || { width: 0.4, height: 0.5, left: 0.3, top: 0.2 },
                attributes: {
                    age: result.estimated_age || 30,
                    gender: result.gender || 'male',
                    emotion: result.expression || 'neutral',
                    glasses: result.has_glasses || false
                }
            },
            timestamp: new Date().toISOString(),
            image_path: result.image_path,
            python_processed: true
        };

        // Actualizar UI
        if (elements.accuracy) elements.accuracy.textContent = systemState.currentFaceData.accuracy + '%';
        if (elements.landmarks) elements.landmarks.textContent = systemState.currentFaceData.landmarks;
        if (elements.scanTime) elements.scanTime.textContent = systemState.currentFaceData.scanTime + 's';
        if (elements.matchConfidence) elements.matchConfidence.textContent = systemState.currentFaceData.matchConfidence + '%';
        if (elements.scannerStatus) elements.scannerStatus.textContent = 'Completado';

        // Generar imagen local
        generateLocalImage('face');

    } else if (scanType === 'id') {
        // Procesar resultado de escaneo de documento
        systemState.currentDocumentData = {
            type: 'Cédula de Identidad',
            quality: result.quality || 'Buena',
            extracted: result.extraction_success || true,
            ocrData: result.ocr_data || {
                name: 'Nombre Extraído',
                idNumber: Math.random().toString().substr(2, 10),
                birthDate: '1990-01-01',
                nationality: 'Nacionalidad'
            },
            timestamp: new Date().toISOString(),
            image_path: result.image_path,
            python_processed: true
        };

        // Actualizar UI
        if (elements.docType) elements.docType.textContent = systemState.currentDocumentData.type;
        if (elements.quality) elements.quality.textContent = systemState.currentDocumentData.quality;
        if (elements.ocrStatus) elements.ocrStatus.textContent = 'Completado';
        if (elements.extractedInfo) elements.extractedInfo.textContent = systemState.currentDocumentData.extracted ? 'Sí' : 'No';

        // Generar imagen local para documento
        generateLocalImage('document');
    }

    // Habilitar envío para verificación
    if (elements.submitData) {
        elements.submitData.disabled = false;
    }
    updateStatusIndicators();
    showComparisonResults();
}

// Función para generar imágenes locales cuando el servidor no está disponible
function generateLocalImage(type) {
    console.log('Generando imagen local para tipo:', type);
    
    if (type === 'face') {
        // Crear una imagen facial simple usando canvas
        const canvas = elements.faceCanvas;
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            // Limpiar canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Fondo
            ctx.fillStyle = '#f0f5ff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Cara
            ctx.fillStyle = '#ffdcb1';
            ctx.beginPath();
            ctx.ellipse(canvas.width/2, canvas.height/2, 120, 150, 0, 0, 2 * Math.PI);
            ctx.fill();
            
            // Ojos
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.ellipse(canvas.width/2 - 40, canvas.height/2 - 20, 25, 20, 0, 0, 2 * Math.PI);
            ctx.ellipse(canvas.width/2 + 40, canvas.height/2 - 20, 25, 20, 0, 0, 2 * Math.PI);
            ctx.fill();
            
            ctx.fillStyle = '#000000';
            ctx.beginPath();
            ctx.ellipse(canvas.width/2 - 40, canvas.height/2 - 20, 12, 12, 0, 0, 2 * Math.PI);
            ctx.ellipse(canvas.width/2 + 40, canvas.height/2 - 20, 12, 12, 0, 0, 2 * Math.PI);
            ctx.fill();
            
            // Boca
            ctx.fillStyle = '#ff9696';
            ctx.beginPath();
            ctx.ellipse(canvas.width/2, canvas.height/2 + 40, 40, 20, 0, 0, Math.PI);
            ctx.fill();
            
            // Texto
            ctx.fillStyle = '#666666';
            ctx.font = '14px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Rostro Simulado', canvas.width/2, canvas.height - 20);
            
            console.log('Imagen facial local generada');
        }
    } else if (type === 'document') {
        // Para documentos, usar un color de fondo con icono
        const docPreview = document.getElementById('document-preview');
        if (docPreview) {
            docPreview.style.backgroundImage = '';
            docPreview.style.backgroundColor = '#f8f9fa';
            docPreview.style.display = 'flex';
            docPreview.style.alignItems = 'center';
            docPreview.style.justifyContent = 'center';
            docPreview.style.flexDirection = 'column';
            docPreview.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                <div style="color: #666; text-align: center;">Documento<br>Simulado</div>
            `;
            console.log('Vista previa de documento local generada');
        }
    }
}

// ===== ENVÍO DE DATOS PARA VERIFICACIÓN =====
async function submitData() {
    console.log('Preparando envío de datos para verificación...');
    
    // Validar que hay datos para enviar
    if (!systemState.currentFaceData && !systemState.currentDocumentData) {
        showModal('Error', 'No hay datos para enviar. Por favor, realice un escaneo primero.');
        return;
    }

    updateSystemStatus('Preparando envío de datos...', 'processing');

    try {
        // Preparar los datos para enviar
        const submissionData = {
            action: 'submit_verification',
            timestamp: new Date().toISOString(),
            session_id: generateSessionId(),
            face_data: systemState.currentFaceData,
            document_data: systemState.currentDocumentData,
            system_info: {
                user_agent: navigator.userAgent,
                timestamp: new Date().toISOString(),
                version: '1.0'
            }
        };

        console.log('Datos a enviar:', submissionData);

        // Mostrar confirmación antes de enviar
        showModal('Confirmar Envío', 
            `¿Está seguro de que desea enviar los datos para verificación?
            
            ${systemState.currentFaceData ? '✅ Datos faciales listos' : '❌ Sin datos faciales'}
            ${systemState.currentDocumentData ? '✅ Datos documentales listos' : '❌ Sin datos documentales'}
            
            Esta acción no se puede deshacer.`,
            [
                { 
                    text: 'Sí, Enviar', 
                    action: () => sendVerificationData(submissionData) 
                },
                { 
                    text: 'Cancelar', 
                    action: () => updateSystemStatus('Envío cancelado', 'warning') 
                }
            ]
        );

    } catch (error) {
        console.error('Error preparando datos:', error);
        showModal('Error', `Error al preparar los datos: ${error.message}`);
        updateSystemStatus('Error en preparación de datos', 'error');
    }
}

// Función para enviar los datos al servidor
async function sendVerificationData(data) {
    updateSystemStatus('Enviando datos al servidor...', 'processing');
    
    try {
        // Simular envío al servidor (reemplaza con tu endpoint real)
        const response = await fetch('../Back-end/Escaner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        console.log('Respuesta del servidor:', response.status);

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();
        console.log('Resultado del servidor:', result);

        if (result.success) {
            // Éxito
            showModal('Envío Exitoso', 
                `Los datos han sido enviados correctamente para verificación.
                
                ID de Verificación: ${result.verification_id || 'N/A'}
                Tiempo estimado: ${result.estimated_time || '1-2 minutos'}
                
                Será notificado cuando los resultados estén disponibles.`);
            
            updateSystemStatus('Datos enviados correctamente', 'success');
            
            // Opcional: limpiar datos después del envío exitoso
            // resetScanner();
            
        } else {
            throw new Error(result.error || 'Error en el servidor');
        }

    } catch (error) {
        console.error('Error enviando datos:', error);
        
        // En caso de error, mostrar opciones
        showModal('Error en Envío', 
            `No se pudieron enviar los datos: ${error.message}. ¿Desea intentarlo de nuevo?`,
            [
                { 
                    text: 'Reintentar', 
                    action: () => sendVerificationData(data) 
                },
                { 
                    text: 'Guardar Localmente', 
                    action: () => saveDataLocally(data) 
                },
                { 
                    text: 'Cancelar', 
                    action: () => updateSystemStatus('Envío fallido', 'error') 
                }
            ]
        );
    }
}

// Función para guardar datos localmente como respaldo
function saveDataLocally(data) {
    try {
        // Guardar en localStorage
        const storageKey = `scan_data_backup_${Date.now()}`;
        localStorage.setItem(storageKey, JSON.stringify(data));
        
        // También ofrecer descarga
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `scan_backup_${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showModal('Datos Guardados', 
            `Los datos se han guardado localmente como respaldo.
            
            Clave de respaldo: ${storageKey}
            También se ha descargado un archivo JSON.
            
            Puede intentar enviarlos más tarde.`);
        
        updateSystemStatus('Datos guardados localmente', 'warning');
        
    } catch (error) {
        console.error('Error guardando localmente:', error);
        showModal('Error', 'No se pudieron guardar los datos localmente.');
    }
}

// ===== FUNCIONES DE CONTROL =====
function resetScanner() {
    // Detener cámara
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    
    // Resetear estado
    systemState.cameraActive = false;
    systemState.scanning = false;
    systemState.currentFaceData = null;
    systemState.currentDocumentData = null;
    
    // Resetear UI
    if (elements.cameraToggle) elements.cameraToggle.textContent = 'Activar';
    if (elements.scannerStatus) elements.scannerStatus.textContent = 'Inactivo';
    if (elements.startScan) elements.startScan.disabled = true;
    if (elements.stopScan) elements.stopScan.disabled = true;
    if (elements.submitData) elements.submitData.disabled = true;
    
    // Limpiar datos biométricos
    const dataElements = ['accuracy', 'landmarks', 'scan-time', 'match-confidence', 
                         'doc-type', 'quality', 'ocr-status', 'extracted-info'];
    dataElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '--';
    });
    
    // Ocultar resultados
    const resultsSection = document.getElementById('results-section');
    if (resultsSection) resultsSection.style.display = 'none';
    
    // Limpiar canvas
    if (elements.faceCanvas) {
        const ctx = elements.faceCanvas.getContext('2d');
        ctx.clearRect(0, 0, elements.faceCanvas.width, elements.faceCanvas.height);
    }
    
    // Limpiar preview de documento
    const docPreview = document.getElementById('document-preview');
    if (docPreview) {
        docPreview.style.backgroundImage = '';
        docPreview.style.backgroundColor = '';
        docPreview.innerHTML = '<div class="doc-icon">📄</div>';
    }
    
    // Limpiar video
    if (elements.cameraFeed) {
        elements.cameraFeed.srcObject = null;
    }
    
    // Limpiar inputs de archivo
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.value = '';
    });
    
    updateSystemStatus('Sistema reiniciado', 'info');
    updateStatusIndicators();
}

function exportData() {
    const data = {
        faceData: systemState.currentFaceData,
        documentData: systemState.currentDocumentData,
        exportTime: new Date().toISOString(),
        systemInfo: {
            version: '1.0',
            exportFormat: 'JSON'
        }
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `scan_data_${Date.now()}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showModal('Exportación Exitosa', 'Datos exportados correctamente en formato JSON.');
}

// ===== NUEVOS BOTONES EN EL HTML =====
function addPythonScanButtons() {
    const controlPanel = document.querySelector('.control-buttons');
    if (!controlPanel) return;
    
    // Crear contenedor para botones Python
    const pythonButtonsContainer = document.createElement('div');
    pythonButtonsContainer.className = 'python-buttons';
    pythonButtonsContainer.style.display = 'flex';
    pythonButtonsContainer.style.gap = '10px';
    pythonButtonsContainer.style.marginTop = '10px';
    pythonButtonsContainer.style.paddingTop = '10px';
    pythonButtonsContainer.style.borderTop = '1px solid #ddd';
    
    const pythonFaceBtn = document.createElement('button');
    pythonFaceBtn.textContent = 'Escáner Python Facial';
    pythonFaceBtn.onclick = () => executePythonScan('face');
    pythonFaceBtn.title = 'Usar escáner Python externo para reconocimiento facial';
    pythonFaceBtn.type = 'button';
    pythonFaceBtn.className = 'python-btn';
    
    const pythonDocBtn = document.createElement('button');
    pythonDocBtn.textContent = 'Escáner Python Documento';
    pythonDocBtn.onclick = () => executePythonScan('id');
    pythonDocBtn.title = 'Usar escáner Python externo para documentos';
    pythonDocBtn.type = 'button';
    pythonDocBtn.className = 'python-btn';
    
    pythonButtonsContainer.appendChild(pythonFaceBtn);
    pythonButtonsContainer.appendChild(pythonDocBtn);
    controlPanel.appendChild(pythonButtonsContainer);
}

// ===== MODAL MEJORADO =====
function showModal(title, message, buttons = null) {
    const modal = document.getElementById('alert-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalFooter = document.querySelector('.modal-footer');
    
    if (!modal || !modalTitle || !modalMessage || !modalFooter) {
        console.error('Elementos del modal no encontrados');
        // Fallback: usar alert nativo
        alert(`${title}\n\n${message}`);
        return;
    }
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Limpiar botones anteriores
    modalFooter.innerHTML = '';
    
    if (buttons && buttons.length > 0) {
        buttons.forEach(btnConfig => {
            const button = document.createElement('button');
            button.textContent = btnConfig.text;
            button.onclick = () => {
                btnConfig.action();
                closeModal();
            };
            modalFooter.appendChild(button);
        });
    } else {
        const okButton = document.createElement('button');
        okButton.textContent = 'Aceptar';
        okButton.onclick = closeModal;
        modalFooter.appendChild(okButton);
    }
    
    modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('alert-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// ===== CONFIGURACIÓN DE EVENT LISTENERS =====
function setupEventListeners() {
    console.log('Configurando event listeners');
    
    // Botón de cámara
    const cameraToggle = document.getElementById('camera-toggle');
    if (cameraToggle) {
        cameraToggle.addEventListener('click', toggleCamera);
        console.log('Event listener añadido al botón de cámara');
    }
    
    // Botones de escaneo
    if (elements.startScan) {
        elements.startScan.addEventListener('click', startScan);
    }
    
    if (elements.stopScan) {
        elements.stopScan.addEventListener('click', stopScan);
    }
    
    if (elements.submitData) {
        elements.submitData.addEventListener('click', submitData);
    }
    
    // Input de archivos
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', handleImageUpload);
    }
    
    const idInput = document.getElementById('id-input');
    if (idInput) {
        idInput.addEventListener('change', handleDocumentUpload);
    }
    
    // Botones adicionales del panel de control
    const resetScannerBtn = document.getElementById('reset-scanner');
    if (resetScannerBtn) {
        resetScannerBtn.addEventListener('click', resetScanner);
    }
    
    const exportDataBtn = document.getElementById('export-data');
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', exportData);
    }
    
    // Modal buttons
    const modalOkBtn = document.getElementById('modal-ok');
    if (modalOkBtn) {
        modalOkBtn.addEventListener('click', closeModal);
    }
    
    const modalCloseBtn = document.querySelector('.modal-close');
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }
    
    console.log('Todos los event listeners configurados');
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando sistema...');
    initializeSystem();
    setupEventListeners();
    addPythonScanButtons();
    console.log('Sistema completamente inicializado');
});

// ===== MANEJO DE ERRORES GLOBALES =====
window.addEventListener('error', function(e) {
    console.error('Error global:', e.error);
});

// ===== EXPORTAR FUNCIONES PARA HTML (backup) =====
window.toggleCamera = toggleCamera;
window.startScan = startScan;
window.stopScan = stopScan;
window.resetScanner = resetScanner;
window.exportData = exportData;
window.handleImageUpload = handleImageUpload;
window.handleDocumentUpload = handleDocumentUpload;
window.closeModal = closeModal;

// ===== ESTILOS DINÁMICOS PARA BOTONES PYTHON =====
const style = document.createElement('style');
style.textContent = `
    .python-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    
    .python-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .python-buttons {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);