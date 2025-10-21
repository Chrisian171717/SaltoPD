// ===== VARIABLES GLOBALES =====
let systemState;
let elements;
let cameraStream = null;
let pollingInterval = null;
let lastCheckTime = Date.now();
let documentTypes = {};

// ===== INICIALIZACIÓN DEL SISTEMA =====
function initializeSystem() {
    console.log('Sistema de escaneo inicializado');
    
    // Inicializar estado del sistema
    systemState = {
        cameraActive: false,
        scanning: false,
        currentFaceData: null,
        currentDocumentData: null,
        currentDocumentType: null
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
    
    // Cargar tipos de documentos
    loadDocumentTypes();
    
    updateSystemStatus('Sistema listo', 'ready');
    updateStatusIndicators();
}

// ===== GESTIÓN DE TIPOS DE DOCUMENTOS =====
async function loadDocumentTypes() {
    try {
        const response = await fetch('Escaner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_document_types'
            })
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                documentTypes = result.document_types;
                console.log('Tipos de documentos cargados:', documentTypes);
                updateDocumentTypeSelector();
            }
        }
    } catch (error) {
        console.error('Error cargando tipos de documentos:', error);
        // Usar tipos por defecto
        documentTypes = {
            'id': { name: 'Cédula de Identidad', fields: [] },
            'passport': { name: 'Pasaporte', fields: [] },
            'driver': { name: 'Licencia de Conducir', fields: [] },
            'other': { name: 'Otro Documento', fields: [] }
        };
    }
}

function updateDocumentTypeSelector() {
    const docTypeElement = document.getElementById('doc-type');
    if (docTypeElement && systemState.currentDocumentData) {
        const docType = systemState.currentDocumentData.document_type || 
                       systemState.currentDocumentData.document_type_name || 
                       'Documento de Identidad';
        docTypeElement.textContent = docType;
    }
}

// ===== FUNCIONES AUXILIARES =====
function generateSessionId() {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function updateSystemStatus(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    if (elements.processingStatusText) {
        elements.processingStatusText.textContent = message;
    }
    
    if (type === 'error' && elements.alertStatusText) {
        elements.alertStatusText.textContent = message;
    }
}

function updateStatusIndicators() {
    if (elements.cameraStatusText) {
        elements.cameraStatusText.textContent = systemState.cameraActive ? 'Activa' : 'Inactiva';
    }
    
    if (elements.verificationStatusText) {
        const hasData = systemState.currentFaceData || systemState.currentDocumentData;
        elements.verificationStatusText.textContent = hasData ? 'Datos listos' : 'Pendiente';
    }
}

// ===== CONTROL DE CÁMARA =====
async function toggleCamera() {
    if (cameraStream) {
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
    
    setTimeout(() => {
        if (systemState.scanning) {
            systemState.scanning = false;
            
            if (elements.scannerStatus) {
                elements.scannerStatus.textContent = 'Completado';
            }
            if (elements.startScan) {
                elements.startScan.disabled = false;
            }
            if (elements.stopScan) {
                elements.stopScan.disabled = true;
            }
            
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
            
            generateLocalImage('face');
            
            if (elements.submitData) {
                elements.submitData.disabled = false;
            }
            
            updateSystemStatus('Escaneo facial completado', 'success');
            updateStatusIndicators();
            showComparisonResults();
        }
    }, 3000);
}

function showComparisonResults() {
    const resultsSection = document.getElementById('results-section');
    if (resultsSection) {
        resultsSection.style.display = 'block';
    }
    
    if (elements.facialMatch && systemState.currentFaceData) {
        elements.facialMatch.textContent = systemState.currentFaceData.matchConfidence + '%';
        elements.facialMatchBar.style.width = systemState.currentFaceData.matchConfidence + '%';
    }
    
    if (elements.docVerification && systemState.currentDocumentData) {
        elements.docVerification.textContent = 'Verificado';
        elements.docVerification.className = 'verification-status verified';
    }
    
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
        
        const imageUrl = URL.createObjectURL(file);
        const canvas = elements.faceCanvas;
        if (canvas) {
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                processUploadedImage();
            };
            
            img.src = imageUrl;
        }
        
        showModal('Imagen Cargada', `Imagen ${file.name} cargada correctamente. Procesando...`);
    }
}

function processUploadedImage() {
    updateSystemStatus('Procesando imagen cargada...', 'processing');
    
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
        
        // Mostrar selector de tipo de documento
        showDocumentTypeSelector(file);
    }
}

function showDocumentTypeSelector(file) {
    const documentTypesList = Object.keys(documentTypes).map(key => ({
        code: key,
        name: documentTypes[key].name
    }));
    
    let optionsHtml = documentTypesList.map(docType => 
        `<button type="button" class="doc-type-option" onclick="processDocumentUpload('${docType.code}', '${file.name}')">
            ${docType.name}
        </button>`
    ).join('');
    
    showModal('Seleccionar Tipo de Documento', 
        `Seleccione el tipo de documento para: <strong>${file.name}</strong>
        <div class="doc-type-options" style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px;">
            ${optionsHtml}
        </div>`,
        [
            { text: 'Cancelar', action: () => {} }
        ]
    );
}

async function processDocumentUpload(documentType, filename) {
    updateSystemStatus(`Procesando ${documentTypes[documentType].name}...`, 'processing');
    
    try {
        // En un sistema real, aquí subirías el archivo al servidor
        // Por ahora, simulamos el procesamiento
        const response = await fetch('Escaner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'process_document',
                document_type: documentType,
                image_path: `uploads/${filename}`
            })
        });

        if (response.ok) {
            const result = await response.json();
            if (result.success) {
                handleDocumentProcessingResult(result, documentType, filename);
            } else {
                throw new Error(result.error || 'Error procesando documento');
            }
        } else {
            throw new Error('Error en la respuesta del servidor');
        }
    } catch (error) {
        console.error('Error procesando documento:', error);
        // Usar datos mock
        useMockDocumentData(documentType, filename);
    }
}

function handleDocumentProcessingResult(result, documentType, filename) {
    systemState.currentDocumentData = {
        document_type: documentTypes[documentType].name,
        document_code: documentType,
        quality: 'Excelente',
        extracted: true,
        ocr_data: result.ocr_data,
        confidence: result.confidence,
        processing_time: result.processing_time,
        image_path: result.image_path,
        fields_detected: result.fields_detected,
        python_processed: false
    };
    
    updateDocumentUI();
    showDocumentDetailsModal(result.ocr_data, documentTypes[documentType].name);
}

function useMockDocumentData(documentType, filename) {
    const mockData = generateMockDocumentData(documentType);
    
    systemState.currentDocumentData = {
        document_type: documentTypes[documentType].name,
        document_code: documentType,
        quality: 'Buena',
        extracted: true,
        ocr_data: mockData,
        confidence: 85.0,
        processing_time: 2.1,
        image_path: `mock_${documentType}_${Date.now()}.jpg`,
        fields_detected: Object.keys(mockData),
        python_processed: false
    };
    
    updateDocumentUI();
    showDocumentDetailsModal(mockData, documentTypes[documentType].name);
}

function generateMockDocumentData(documentType) {
    const mockData = {
        'id': {
            'name': 'CARLOS ANDRÉS MARTÍNEZ ROJAS',
            'id_number': '18.765.432-1',
            'birth_date': '20-08-1990',
            'nationality': 'CHILENA',
            'issue_date': '15-07-2021',
            'expiry_date': '15-07-2031',
            'sex': 'M',
            'birth_place': 'VALPARAÍSO'
        },
        'passport': {
            'passport_number': 'PB7654321',
            'surname': 'MARTÍNEZ ROJAS',
            'given_names': 'CARLOS ANDRÉS',
            'nationality': 'CHILE',
            'birth_date': '20 AUG 1990',
            'issue_date': '10 JAN 2024',
            'expiry_date': '10 JAN 2034',
            'authority': 'SANTIAGO CHILE'
        },
        'driver': {
            'license_number': 'A98765432-1',
            'name': 'CARLOS ANDRÉS MARTÍNEZ ROJAS',
            'birth_date': '20-08-1990',
            'issue_date': '05-12-2023',
            'expiry_date': '05-12-2033',
            'categories': 'A B C',
            'address': 'AV. COSTANERA 567, VALPARAÍSO'
        },
        'other': {
            'document_type': 'Credencial de Estudiante',
            'document_number': 'UV202400567',
            'name': 'CARLOS MARTÍNEZ',
            'issue_date': '2024-03-15',
            'institution': 'UNIVERSIDAD DE VALPARAÍSO'
        }
    };
    
    return mockData[documentType] || mockData['other'];
}

function updateDocumentUI() {
    if (elements.docType) {
        elements.docType.textContent = systemState.currentDocumentData.document_type;
    }
    if (elements.quality) {
        elements.quality.textContent = systemState.currentDocumentData.quality;
    }
    if (elements.ocrStatus) {
        elements.ocrStatus.textContent = 'Completado';
    }
    if (elements.extractedInfo) {
        elements.extractedInfo.textContent = 'Sí';
    }
    
    // Actualizar preview del documento
    const docPreview = document.getElementById('document-preview');
    if (docPreview) {
        docPreview.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                <div style="font-weight: bold; margin-bottom: 5px;">${systemState.currentDocumentData.document_type}</div>
                <div style="font-size: 12px; color: #666;">${systemState.currentDocumentData.fields_detected.length} campos detectados</div>
                <div style="font-size: 10px; color: #888; margin-top: 10px;">Confianza: ${systemState.currentDocumentData.confidence}%</div>
            </div>
        `;
    }
    
    if (elements.submitData) {
        elements.submitData.disabled = false;
    }
    
    updateStatusIndicators();
    showComparisonResults();
}

function showDocumentDetailsModal(ocrData, documentType) {
    let detailsHtml = `
        <div style="max-height: 300px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse;">
    `;
    
    for (const [key, value] of Object.entries(ocrData)) {
        const formattedKey = key.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
        
        detailsHtml += `
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 8px; font-weight: bold; width: 40%;">${formattedKey}:</td>
                <td style="padding: 8px;">${value}</td>
            </tr>
        `;
    }
    
    detailsHtml += `
            </table>
        </div>
    `;
    
    showModal(`Detalles del ${documentType}`, 
        `Información extraída del documento:
        ${detailsHtml}`,
        [
            { text: 'Aceptar', action: () => {} }
        ]
    );
}

// ===== FUNCIÓN PARA LANZAR INTERFAZ PYTHON =====
async function launchPythonUI(scanType) {
    updateSystemStatus(`Lanzando interfaz Python de ${scanType === 'face' ? 'reconocimiento facial' : 'escaneo de documento'}...`, 'processing');
    
    try {
        const response = await fetch('Escaner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'launch_python_ui',
                scan_type: scanType,
                session_id: generateSessionId(),
                timestamp: new Date().toISOString()
            })
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            showModal('Interfaz Python Lanzada', 
                `La ventana de OpenCV debería abrirse en cualquier momento.
                
                📌 Instrucciones:
                • F1: Capturar rostro
                • F2: Capturar documento
                • M: Cambiar modo
                • ESC: Cerrar ventana
                
                Las imágenes capturadas se guardarán automáticamente y aparecerán en esta interfaz.`,
                [
                    { text: 'Entendido', action: () => startPollingForImages() },
                    { text: 'Cerrar', action: () => {} }
                ]
            );
            updateSystemStatus('Interfaz Python lanzada correctamente', 'success');
        } else {
            throw new Error(result.error || 'Error desconocido al lanzar Python');
        }

    } catch (error) {
        console.error('Error lanzando interfaz Python:', error);
        showModal('Error', 
            `No se pudo lanzar la interfaz Python: ${error.message}
            
            Posibles soluciones:
            • Verifique que Python esté instalado
            • Instale OpenCV: pip install opencv-python
            • Verifique que su cámara esté conectada
            
            ¿Desea usar el escaneo web en su lugar?`,
            [
                { text: 'Usar Escaneo Web', action: () => toggleCamera().then(() => startWebScan()) },
                { text: 'Usar Datos de Prueba', action: () => useMockData(scanType) },
                { text: 'Cancelar', action: () => {} }
            ]
        );
        updateSystemStatus('Error lanzando interfaz Python', 'error');
    }
}

// ===== FUNCIÓN PARA EJECUTAR ESCANEO PYTHON =====
async function executePythonScan(scanType) {
    const sessionId = generateSessionId();
    
    updateSystemStatus(`Iniciando escaneo Python para ${scanType}...`, 'processing');
    
    try {
        const response = await fetch('../Back-end/Escaner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'execute_python_scan',
                scan_type: scanType,
                session_id: sessionId,
                timestamp: new Date().toISOString()
            })
        });

        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            if (result.is_mock) {
                showModal('Modo Prueba', 
                    'Se están usando datos de prueba. El escaneo Python real no está disponible.',
                    [
                        { text: 'Usar Datos Prueba', action: () => handlePythonScanResult(result.data, scanType) },
                        { text: 'Cancelar', action: () => {} }
                    ]
                );
            } else {
                handlePythonScanResult(result.data, scanType);
                updateSystemStatus(`Escaneo ${scanType} completado`, 'success');
            }
        } else {
            throw new Error(result.error || 'Error en el servidor');
        }

    } catch (error) {
        console.error('Error en escaneo Python:', error);
        showModal('Error', 
            `No se pudo ejecutar el escaneo Python: ${error.message}`,
            [
                { text: 'Usar Datos Prueba', action: () => useMockData(scanType) },
                { text: 'Intentar Cámara Web', action: () => toggleCamera().then(() => startWebScan()) },
                { text: 'Cancelar', action: () => {} }
            ]
        );
        updateSystemStatus('Error en escaneo Python', 'error');
    }
}

// ===== FUNCIÓN PARA MONITOREAR IMÁGENES CAPTURADAS =====
function startPollingForImages() {
    console.log('🔍 Iniciando monitoreo de imágenes capturadas...');
    
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    showMonitoringIndicator();
    
    pollingInterval = setInterval(async () => {
        try {
            const response = await fetch('Escaner.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'check_new_images',
                    last_check: lastCheckTime
                })
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.new_images && result.new_images.length > 0) {
                    console.log('📸 Nuevas imágenes detectadas:', result.new_images);
                    processNewImages(result.new_images);
                    lastCheckTime = Date.now();
                }
            }
        } catch (error) {
            console.error('Error verificando imágenes:', error);
        }
    }, 3000);
    
    setTimeout(() => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
            hideMonitoringIndicator();
            console.log('⏹️ Monitoreo de imágenes detenido por timeout');
        }
    }, 300000);
}

function stopPollingForImages() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
        hideMonitoringIndicator();
        console.log('⏹️ Monitoreo de imágenes detenido manualmente');
    }
}

function processNewImages(images) {
    images.forEach(image => {
        if (image.type === 'face') {
            processNewFaceImage(image);
        } else if (['id', 'passport', 'driver', 'document'].includes(image.type)) {
            processNewDocumentImage(image);
        }
    });
}

function processNewFaceImage(image) {
    systemState.currentFaceData = {
        landmarks: 68,
        accuracy: '95.0%',
        scanTime: '2.0s',
        matchConfidence: '92.0%',
        features: {
            faceId: `python_face_${Date.now()}`,
            confidence: 0.95
        },
        image_path: image.path,
        python_processed: true
    };
    
    loadImageToCanvas(image.path, elements.faceCanvas);
    
    if (elements.accuracy) elements.accuracy.textContent = systemState.currentFaceData.accuracy;
    if (elements.landmarks) elements.landmarks.textContent = systemState.currentFaceData.landmarks;
    if (elements.scanTime) elements.scanTime.textContent = systemState.currentFaceData.scanTime;
    if (elements.matchConfidence) elements.matchConfidence.textContent = systemState.currentFaceData.matchConfidence;
    if (elements.scannerStatus) elements.scannerStatus.textContent = 'Completado (Python)';
    
    showModal('✅ Rostro Capturado', 'Se ha capturado y procesado un nuevo rostro desde Python.');
    updateAfterCapture();
}

function processNewDocumentImage(image) {
    const documentType = image.document_type || 'other';
    const documentName = documentTypes[documentType]?.name || 'Documento';
    
    systemState.currentDocumentData = {
        document_type: documentName,
        document_code: documentType,
        quality: 'Excelente',
        extracted: true,
        ocr_data: generateMockDocumentData(documentType),
        confidence: 88.0,
        processing_time: 2.3,
        image_path: image.path,
        fields_detected: ['name', 'document_number', 'issue_date'],
        python_processed: true
    };
    
    const docPreview = document.getElementById('document-preview');
    if (docPreview) {
        docPreview.style.backgroundImage = `url(../${image.path})`;
        docPreview.style.backgroundSize = 'cover';
        docPreview.innerHTML = '';
    }
    
    updateDocumentUI();
    showModal('✅ Documento Capturado', `Se ha capturado y procesado un nuevo ${documentName} desde Python.`);
    updateAfterCapture();
}

function updateAfterCapture() {
    if (elements.submitData) {
        elements.submitData.disabled = false;
    }
    updateStatusIndicators();
    showComparisonResults();
}

function loadImageToCanvas(imagePath, canvas) {
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const img = new Image();
    
    img.onload = function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
        const x = (canvas.width / 2) - (img.width / 2) * scale;
        const y = (canvas.height / 2) - (img.height / 2) * scale;
        
        ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
    };
    
    img.onerror = function() {
        console.error('Error cargando imagen:', imagePath);
        generateLocalImage('face');
    };
    
    img.src = '../' + imagePath;
}

// ===== MANEJO DE RESULTADOS PYTHON =====
function handlePythonScanResult(result, scanType) {
    if (scanType === 'face') {
        systemState.currentFaceData = {
            landmarks: result.landmarks || 68,
            accuracy: result.confidence ? (result.confidence * 100).toFixed(1) : 95.0,
            scanTime: result.processing_time || 2.5,
            matchConfidence: result.confidence ? (result.confidence * 100).toFixed(1) : 95.0,
            features: {
                faceId: result.face_id || `face_${Date.now()}`,
                confidence: result.confidence || 0.95
            },
            timestamp: new Date().toISOString(),
            image_path: result.image_path,
            python_processed: true
        };

        if (elements.accuracy) elements.accuracy.textContent = systemState.currentFaceData.accuracy + '%';
        if (elements.landmarks) elements.landmarks.textContent = systemState.currentFaceData.landmarks;
        if (elements.scanTime) elements.scanTime.textContent = systemState.currentFaceData.scanTime + 's';
        if (elements.matchConfidence) elements.matchConfidence.textContent = systemState.currentFaceData.matchConfidence + '%';
        if (elements.scannerStatus) elements.scannerStatus.textContent = 'Completado';

        generateLocalImage('face');

    } else if (scanType === 'id') {
        systemState.currentDocumentData = {
            document_type: result.document_type_name || 'Documento de Identidad',
            document_code: result.document_type || 'id',
            quality: result.quality || 'Buena',
            extracted: result.extraction_success || true,
            ocr_data: result.ocr_data || generateMockDocumentData('id'),
            confidence: result.confidence || 85.0,
            processing_time: result.processing_time || 1.8,
            image_path: result.image_path,
            fields_detected: result.fields_detected || ['name', 'id_number'],
            python_processed: true
        };

        updateDocumentUI();
    }

    if (elements.submitData) {
        elements.submitData.disabled = false;
    }
    updateStatusIndicators();
    showComparisonResults();
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
        document_type: 'id',
        document_type_name: 'Cédula de Identidad',
        ocr_data: generateMockDocumentData('id'),
        confidence: 87.5,
        processing_time: 1.8,
        image_path: null,
        is_mock_data: true
    };
    
    handlePythonScanResult(mockData, scanType);
    updateSystemStatus(`Escaneo ${scanType} completado (modo prueba)`, 'success');
}

// Función para generar imágenes locales cuando el servidor no está disponible
function generateLocalImage(type) {
    console.log('Generando imagen local para tipo:', type);
    
    if (type === 'face') {
        const canvas = elements.faceCanvas;
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#f0f5ff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#ffdcb1';
            ctx.beginPath();
            ctx.ellipse(canvas.width/2, canvas.height/2, 120, 150, 0, 0, 2 * Math.PI);
            ctx.fill();
            
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
            
            ctx.fillStyle = '#ff9696';
            ctx.beginPath();
            ctx.ellipse(canvas.width/2, canvas.height/2 + 40, 40, 20, 0, 0, Math.PI);
            ctx.fill();
            
            ctx.fillStyle = '#666666';
            ctx.font = '14px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Rostro Simulado', canvas.width/2, canvas.height - 20);
            
            console.log('Imagen facial local generada');
        }
    } else if (type === 'document') {
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
    
    if (!systemState.currentFaceData && !systemState.currentDocumentData) {
        showModal('Error', 'No hay datos para enviar. Por favor, realice un escaneo primero.');
        return;
    }

    updateSystemStatus('Preparando envío de datos...', 'processing');

    try {
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

        showModal('Confirmar Envío', 
            `¿Está seguro de que desea enviar los datos para verificación?
            
            ${systemState.currentFaceData ? '✅ Datos faciales listos' : '❌ Sin datos faciales'}
            ${systemState.currentDocumentData ? `✅ ${systemState.currentDocumentData.document_type} listo` : '❌ Sin datos documentales'}
            
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
            showModal('Envío Exitoso', 
                `Los datos han sido enviados correctamente para verificación.
                
                ID de Verificación: ${result.verification_id || 'N/A'}
                Tiempo estimado: ${result.estimated_time || '1-2 minutos'}
                
                Será notificado cuando los resultados estén disponibles.`);
            
            updateSystemStatus('Datos enviados correctamente', 'success');
            
        } else {
            throw new Error(result.error || 'Error en el servidor');
        }

    } catch (error) {
        console.error('Error enviando datos:', error);
        
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
        const storageKey = `scan_data_backup_${Date.now()}`;
        localStorage.setItem(storageKey, JSON.stringify(data));
        
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
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    
    stopPollingForImages();
    
    systemState.cameraActive = false;
    systemState.scanning = false;
    systemState.currentFaceData = null;
    systemState.currentDocumentData = null;
    systemState.currentDocumentType = null;
    
    if (elements.cameraToggle) elements.cameraToggle.textContent = 'Activar';
    if (elements.scannerStatus) elements.scannerStatus.textContent = 'Inactivo';
    if (elements.startScan) elements.startScan.disabled = true;
    if (elements.stopScan) elements.stopScan.disabled = true;
    if (elements.submitData) elements.submitData.disabled = true;
    
    const dataElements = ['accuracy', 'landmarks', 'scan-time', 'match-confidence', 
                         'doc-type', 'quality', 'ocr-status', 'extracted-info'];
    dataElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '--';
    });
    
    const resultsSection = document.getElementById('results-section');
    if (resultsSection) resultsSection.style.display = 'none';
    
    if (elements.faceCanvas) {
        const ctx = elements.faceCanvas.getContext('2d');
        ctx.clearRect(0, 0, elements.faceCanvas.width, elements.faceCanvas.height);
    }
    
    const docPreview = document.getElementById('document-preview');
    if (docPreview) {
        docPreview.style.backgroundImage = '';
        docPreview.style.backgroundColor = '';
        docPreview.innerHTML = '<div class="doc-icon">📄</div>';
    }
    
    if (elements.cameraFeed) {
        elements.cameraFeed.srcObject = null;
    }
    
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

// ===== AGREGAR BOTONES PYTHON A LA INTERFAZ =====
function addPythonScanButtons() {
    const controlPanel = document.querySelector('.control-buttons');
    if (!controlPanel) {
        console.warn('No se encontró el panel de control para agregar botones Python');
        return;
    }
    
    if (document.querySelector('.python-buttons')) {
        return;
    }
    
    const pythonButtonsContainer = document.createElement('div');
    pythonButtonsContainer.className = 'python-buttons';
    pythonButtonsContainer.style.cssText = `
        display: flex;
        gap: 10px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #667eea;
        flex-wrap: wrap;
        width: 100%;
    `;
    
    const pythonTitle = document.createElement('div');
    pythonTitle.textContent = '🐍 Interfaz Python con OpenCV';
    pythonTitle.style.cssText = `
        width: 100%;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 10px;
        font-size: 14px;
        text-align: center;
    `;
    pythonButtonsContainer.appendChild(pythonTitle);
    
    const buttonsRow = document.createElement('div');
    buttonsRow.style.cssText = `
        display: flex;
        gap: 10px;
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    `;
    
    const pythonFaceBtn = document.createElement('button');
    pythonFaceBtn.textContent = '🧑‍🦰 Abrir Escáner Facial Python';
    pythonFaceBtn.onclick = () => launchPythonUI('face');
    pythonFaceBtn.title = 'Abre una ventana de OpenCV para escaneo facial en tiempo real';
    pythonFaceBtn.type = 'button';
    pythonFaceBtn.className = 'python-ui-btn';
    
    const pythonDocBtn = document.createElement('button');
    pythonDocBtn.textContent = '🪪 Abrir Escáner de Documento Python';
    pythonDocBtn.onclick = () => launchPythonUI('id');
    pythonDocBtn.title = 'Abre una ventana de OpenCV para escaneo de documentos';
    pythonDocBtn.type = 'button';
    pythonDocBtn.className = 'python-ui-btn';
    
    const stopMonitorBtn = document.createElement('button');
    stopMonitorBtn.textContent = '⏹️ Detener Monitoreo';
    stopMonitorBtn.onclick = stopPollingForImages;
    stopMonitorBtn.title = 'Detiene el monitoreo de imágenes capturadas';
    stopMonitorBtn.type = 'button';
    stopMonitorBtn.className = 'python-stop-btn';
    
    buttonsRow.appendChild(pythonFaceBtn);
    buttonsRow.appendChild(pythonDocBtn);
    buttonsRow.appendChild(stopMonitorBtn);
    pythonButtonsContainer.appendChild(buttonsRow);
    controlPanel.appendChild(pythonButtonsContainer);
    
    console.log('✅ Botones Python agregados a la interfaz');
}

// ===== INDICADOR DE MONITOREO =====
function showMonitoringIndicator() {
    let indicator = document.getElementById('monitoring-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'monitoring-indicator';
        indicator.className = 'monitoring-indicator';
        indicator.innerHTML = '🔍 Monitoreando capturas de Python...';
        document.body.appendChild(indicator);
    }
}

function hideMonitoringIndicator() {
    const indicator = document.getElementById('monitoring-indicator');
    if (indicator) {
        indicator.remove();
    }
}

// ===== MODAL MEJORADO =====
function showModal(title, message, buttons = null) {
    const modal = document.getElementById('alert-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalFooter = document.querySelector('.modal-footer');
    
    if (!modal || !modalTitle || !modalMessage || !modalFooter) {
        console.error('Elementos del modal no encontrados');
        alert(`${title}\n\n${message}`);
        return;
    }
    
    modalTitle.textContent = title;
    modalMessage.innerHTML = message;
    
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
    
    const cameraToggle = document.getElementById('camera-toggle');
    if (cameraToggle) {
        cameraToggle.addEventListener('click', toggleCamera);
    }
    
    if (elements.startScan) {
        elements.startScan.addEventListener('click', startScan);
    }
    
    if (elements.stopScan) {
        elements.stopScan.addEventListener('click', stopScan);
    }
    
    if (elements.submitData) {
        elements.submitData.addEventListener('click', submitData);
    }
    
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', handleImageUpload);
    }
    
    const idInput = document.getElementById('id-input');
    if (idInput) {
        idInput.addEventListener('change', handleDocumentUpload);
    }
    
    const resetScannerBtn = document.getElementById('reset-scanner');
    if (resetScannerBtn) {
        resetScannerBtn.addEventListener('click', resetScanner);
    }
    
    const exportDataBtn = document.getElementById('export-data');
    if (exportDataBtn) {
        exportDataBtn.addEventListener('click', exportData);
    }
    
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

// ===== ESTILOS DINÁMICOS =====
function addDynamicStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .python-ui-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
        }
        
        .python-ui-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        
        .python-ui-btn:active {
            transform: translateY(-1px);
        }
        
        .python-stop-btn {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 4px 6px rgba(255, 107, 107, 0.3);
        }
        
        .python-stop-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(255, 107, 107, 0.4);
            background: linear-gradient(135deg, #ee5a6f 0%, #ff6b6b 100%);
        }
        
        .python-buttons {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .monitoring-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
            z-index: 1000;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.05); }
        }
        
        .doc-type-option {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: left;
        }
        
        .doc-type-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(79, 172, 254, 0.3);
        }
        
        .doc-type-options {
            max-height: 200px;
            overflow-y: auto;
        }
    `;
    document.head.appendChild(style);
}

// ===== INICIALIZACIÓN =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando sistema...');
    initializeSystem();
    setupEventListeners();
    addDynamicStyles();
    addPythonScanButtons();
    console.log('Sistema completamente inicializado');
});

// ===== MANEJO DE ERRORES GLOBALES =====
window.addEventListener('error', function(e) {
    console.error('Error global:', e.error);
});

// ===== EXPORTAR FUNCIONES PARA HTML =====
window.toggleCamera = toggleCamera;
window.startScan = startScan;
window.stopScan = stopScan;
window.resetScanner = resetScanner;
window.exportData = exportData;
window.handleImageUpload = handleImageUpload;
window.handleDocumentUpload = handleDocumentUpload;
window.closeModal = closeModal;
window.launchPythonUI = launchPythonUI;
window.executePythonScan = executePythonScan;
window.startPollingForImages = startPollingForImages;
window.stopPollingForImages = stopPollingForImages;
window.processDocumentUpload = processDocumentUpload;