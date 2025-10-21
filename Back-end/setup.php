<?php
// Back-end/setup.php - Script de configuración del sistema
header('Content-Type: application/json; charset=utf-8');

class SystemSetup {
    public function checkRequirements() {
        $results = [];
        
        // Verificar PHP
        $results['php'] = [
            'version' => PHP_VERSION,
            'min_required' => '7.4',
            'status' => version_compare(PHP_VERSION, '7.4.0') >= 0 ? 'ok' : 'error'
        ];
        
        // Verificar extensiones
        $extensions = ['json', 'mbstring', 'gd'];
        foreach ($extensions as $ext) {
            $results['extensions'][$ext] = [
                'loaded' => extension_loaded($ext),
                'status' => extension_loaded($ext) ? 'ok' : 'warning'
            ];
        }
        
        // Verificar permisos de directorios
        $dirs = ['../faces', '../ids', './verifications', './python_scripts'];
        foreach ($dirs as $dir) {
            $writable = is_writable($dir) || (!file_exists($dir) && is_writable(dirname($dir)));
            $results['directories'][$dir] = [
                'exists' => file_exists($dir),
                'writable' => $writable,
                'status' => $writable ? 'ok' : 'error'
            ];
        }
        
        // Verificar Python
        $pythonCheck = $this->checkPython();
        $results['python'] = $pythonCheck;
        
        return $results;
    }
    
    private function checkPython() {
        $commands = ['python', 'python3', 'py'];
        $result = ['available' => false, 'version' => null, 'command' => null];
        
        foreach ($commands as $cmd) {
            $output = [];
            $returnCode = 0;
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("$cmd --version 2>&1", $output, $returnCode);
            } else {
                exec("$cmd --version 2>&1", $output, $returnCode);
            }
            
            if ($returnCode === 0 && !empty($output)) {
                $result['available'] = true;
                $result['version'] = $output[0];
                $result['command'] = $cmd;
                break;
            }
        }
        
        $result['status'] = $result['available'] ? 'ok' : 'error';
        return $result;
    }
    
    public function createDirectories() {
        $dirs = ['../faces', '../ids', './verifications', './python_scripts'];
        $results = [];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                $success = mkdir($dir, 0755, true);
                $results[$dir] = $success ? 'created' : 'failed';
            } else {
                $results[$dir] = 'exists';
            }
        }
        
        return $results;
    }
}

// Manejar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $setup = new SystemSetup();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'check':
                echo json_encode(['success' => true, 'requirements' => $setup->checkRequirements()]);
                break;
            case 'setup':
                echo json_encode(['success' => true, 'directories' => $setup->createDirectories()]);
                break;
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        }
    } else {
        echo json_encode(['success' => true, 'system' => 'Setup disponible']);
    }
}
?>