<?php
/**
 * FusionPBX - API Router
 * 
 * Simple router for RESTful API endpoints.
 * Routes requests to appropriate controllers based on URL pattern.
 * 
 * Usage: Place this file in app/models/api/index.php
 * Access via: /app/models/api/index.php?resource=users&id=123
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

// Include Eloquent bootstrap
require_once(__DIR__ . '/../eloquent_bootstrap.php');

// Autoload controllers
spl_autoload_register(function ($class) {
    $prefix = 'FusionPBX\\Controllers\\';
    $base_dir = __DIR__ . '/../controllers/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Get request info
$resource = $_GET['resource'] ?? '';
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// Route to controller
try {
    switch ($resource) {
        case 'users':
            $controller = new \FusionPBX\Controllers\UserController();
            
            // Handle sub-resources
            if ($id && $action === 'permissions') {
                $controller->permissions($id);
            } elseif ($id && $action === 'settings' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->settings($id);
            } elseif ($id && $action === 'settings' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
                $controller->updateSetting($id);
            } else {
                $_GET['id'] = $id;
                $controller->handle();
            }
            break;
            
        case 'extensions':
            $controller = new \FusionPBX\Controllers\ExtensionController();
            $_GET['id'] = $id;
            $controller->handle();
            break;
            
        case 'groups':
            $controller = new \FusionPBX\Controllers\GroupController();
            
            if ($id && $action === 'permissions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
                $controller->permissions($id);
            } elseif ($id && $action === 'permissions' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->grantPermission($id);
            } elseif ($id && $action === 'permissions' && $_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $permission_id = $_GET['permission_id'] ?? null;
                $controller->revokePermission($id, $permission_id);
            } else {
                $_GET['id'] = $id;
                $controller->handle();
            }
            break;
            
        case 'dashboards':
            $controller = new \FusionPBX\Controllers\DashboardController();
            $_GET['id'] = $id;
            $controller->handle();
            break;
            
        case 'permissions':
            $controller = new \FusionPBX\Controllers\PermissionController();
            
            if ($action === 'by-application' && $id) {
                $controller->byApplication($id);
            } else {
                $_GET['id'] = $id;
                $controller->handle();
            }
            break;
            
        default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Resource not found',
                'available_resources' => [
                    'users',
                    'extensions',
                    'groups',
                    'dashboards',
                    'permissions'
                ]
            ], JSON_PRETTY_PRINT);
            exit;
    }
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
