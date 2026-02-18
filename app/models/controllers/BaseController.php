<?php
/**
 * FusionPBX - Base API Controller
 * 
 * Base controller providing common functionality for RESTful API endpoints.
 * Handles authentication, permissions, JSON responses, and error handling.
 * 
 * @package    FusionPBX
 * @subpackage Controllers
 */

namespace FusionPBX\Controllers;

abstract class BaseController
{
    /**
     * Session data
     */
    protected $session;
    
    /**
     * Domain UUID from session
     */
    protected $domain_uuid;
    
    /**
     * User UUID from session
     */
    protected $user_uuid;
    
    /**
     * HTTP request method
     */
    protected $method;
    
    /**
     * Request parameters
     */
    protected $params = [];
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize Eloquent
        require_once(__DIR__ . '/../eloquent_bootstrap.php');
        
        // Get session data if available
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->session = $_SESSION ?? [];
        $this->domain_uuid = $this->session['domain_uuid'] ?? null;
        $this->user_uuid = $this->session['user_uuid'] ?? null;
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Parse request data
        $this->parseRequest();
    }
    
    /**
     * Parse request data based on method
     */
    protected function parseRequest()
    {
        switch ($this->method) {
            case 'GET':
                $this->params = $_GET;
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                // Try to parse JSON body
                $input = file_get_contents('php://input');
                $json = json_decode($input, true);
                
                if ($json !== null) {
                    $this->params = $json;
                } else {
                    $this->params = $_POST;
                }
                break;
        }
    }
    
    /**
     * Check if user has required permission
     *
     * @param string $permission Permission name
     * @return bool
     */
    protected function hasPermission($permission)
    {
        // If no permission system loaded, use FusionPBX's permission_exists
        if (function_exists('permission_exists')) {
            return permission_exists($permission);
        }
        
        // Fallback: check via Eloquent if User model available
        if ($this->user_uuid && class_exists('FusionPBX\\Models\\User')) {
            $user = \FusionPBX\Models\User::find($this->user_uuid);
            return $user ? $user->hasPermission($permission) : false;
        }
        
        return false;
    }
    
    /**
     * Require permission or die with 403
     *
     * @param string $permission Permission name
     */
    protected function requirePermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            $this->forbidden("Permission required: {$permission}");
        }
    }
    
    /**
     * Require authentication
     */
    protected function requireAuth()
    {
        if (empty($this->user_uuid) || empty($this->domain_uuid)) {
            $this->unauthorized('Authentication required');
        }
    }
    
    /**
     * Validate domain ownership of a resource
     *
     * @param object $resource Model instance
     */
    protected function validateDomainOwnership($resource)
    {
        if (isset($resource->domain_uuid) && $resource->domain_uuid !== $this->domain_uuid) {
            $this->forbidden('Access denied: resource belongs to different domain');
        }
    }
    
    /**
     * Send JSON response
     *
     * @param mixed $data Response data
     * @param int $code HTTP status code
     */
    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     */
    protected function success($data = null, $message = 'Success')
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->json($response, 200);
    }
    
    /**
     * Send created response (201)
     *
     * @param mixed $data Created resource
     * @param string $message Success message
     */
    protected function created($data, $message = 'Resource created')
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }
    
    /**
     * Send error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     */
    protected function error($message, $code = 400)
    {
        $this->json([
            'success' => false,
            'error' => $message
        ], $code);
    }
    
    /**
     * Send 400 Bad Request
     *
     * @param string $message Error message
     */
    protected function badRequest($message = 'Bad Request')
    {
        $this->error($message, 400);
    }
    
    /**
     * Send 401 Unauthorized
     *
     * @param string $message Error message
     */
    protected function unauthorized($message = 'Unauthorized')
    {
        $this->error($message, 401);
    }
    
    /**
     * Send 403 Forbidden
     *
     * @param string $message Error message
     */
    protected function forbidden($message = 'Forbidden')
    {
        $this->error($message, 403);
    }
    
    /**
     * Send 404 Not Found
     *
     * @param string $message Error message
     */
    protected function notFound($message = 'Not Found')
    {
        $this->error($message, 404);
    }
    
    /**
     * Send 422 Unprocessable Entity (validation error)
     *
     * @param array $errors Validation errors
     */
    protected function validationError($errors)
    {
        $this->json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }
    
    /**
     * Send 500 Internal Server Error
     *
     * @param string $message Error message
     */
    protected function serverError($message = 'Internal Server Error')
    {
        $this->error($message, 500);
    }
    
    /**
     * Get parameter from request
     *
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function param($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
    
    /**
     * Get all parameters
     *
     * @return array
     */
    protected function params()
    {
        return $this->params;
    }
    
    /**
     * Validate required parameters
     *
     * @param array $required Required parameter names
     * @return array Validation errors (empty if valid)
     */
    protected function validateRequired(array $required)
    {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($this->params[$field]) || $this->params[$field] === '') {
                $errors[$field] = "The {$field} field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Handle request routing
     */
    abstract public function handle();
}
