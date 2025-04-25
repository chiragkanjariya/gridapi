<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
use App\Controllers\APIController;

class API {
    private $controller = [];
    private $allowedOrigins = ['http://localhost', 'https://yourdomain.com'];
    private $routes = [];

    /**
     * API constructor.
     * Initializes route patterns and handles CORS headers.
     */
    public function __construct() {
        // Define all ATTOM property API routes
        $this->routes = [
            // Diagnostic endpoint
            '/grid-api\/api\/diagnostic\/test\/?$/' => function ($params) {
                return $this->handle(array_merge(['diagnostic', 'test'], $params));
            },
            
            // Legacy endpoint (maintained for backwards compatibility)
            '/grid-api\/api\/property\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'retrieve'], $params));
            },
            
            // New ATTOM Data API Endpoints
            '/grid-api\/api\/property\/address\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'address'], $params));
            },
            '/grid-api\/api\/property\/basicprofile\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'basicprofile'], $params));
            },
            '/grid-api\/api\/property\/buildingpermits\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'buildingpermits'], $params));
            },
            '/grid-api\/api\/property\/detail\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'detail'], $params));
            },
            '/grid-api\/api\/property\/detailowner\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'detailowner'], $params));
            },
            '/grid-api\/api\/property\/expandedprofile\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'expandedprofile'], $params));
            },
            '/grid-api\/api\/property\/id\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'id'], $params));
            },
            '/grid-api\/api\/property\/snapshot\/?$/' => function ($params) {
                return $this->handle(array_merge(['properties', 'snapshot'], $params));
            }
        ];

        $http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';
        if (in_array($http_origin, $this->allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . $http_origin);
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
            header('Access-Control-Allow-Credentials: true');
            header('Content-Type: application/json');
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                exit(0);
            }
        }

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->dispatch($requestUri);
    }

    /**
     * Dispatches the request to the appropriate handler based on the URL path.
     *
     * @param string $requestUri The request URI path.
     */
    private function dispatch($requestUri) {
        // Debug log
        error_log("Dispatching request: " . $requestUri);
        
        foreach ($this->routes as $pattern => $handler) {
            error_log("Checking pattern: " . $pattern);
            if (preg_match($pattern, $requestUri, $matches)) {
                error_log("Pattern matched: " . $pattern . " - Matches: " . json_encode($matches));
                array_shift($matches);
                $params = [];
                if (!empty($matches)) {
                    $params = ['id' => $matches[0]];
                }
                call_user_func($handler, $params);
                return;
            }
        }
        
        // Additional pattern matching if previous patterns failed
        if (strpos($requestUri, '/grid-api/api/property/') !== false) {
            $parts = explode('/', $requestUri);
            $endpoint = end($parts);
            error_log("Fallback handling for endpoint: " . $endpoint);
            $this->handle(['properties', $endpoint, []]);
            return;
        }
        
        // Check for diagnostic endpoint using fallback pattern
        if (strpos($requestUri, '/grid-api/api/diagnostic/') !== false) {
            $parts = explode('/', $requestUri);
            $endpoint = end($parts);
            error_log("Fallback handling for diagnostic endpoint: " . $endpoint);
            $this->handle(['diagnostic', $endpoint, []]);
            return;
        }
        
        http_response_code(404);
        echo json_encode(array(
            'status' => 'error',
            'message' => 'Invalid endpoint: ' . $requestUri,
        ));
    }

    /**
     * Handles the request based on the provided parameters.
     *
     * @param array $params Parameters extracted from the URL.
     */
    private function handle($params) {
        $_REQUEST['operation'] = $params[0];
        $_REQUEST['mode'] = $params[1];
        $this->controller = APIController::getInstance();
        $operation = $this->controller->Request();
        if (!$operation) {
            echo json_encode(array(
                'status' => 'error',
                'message' => 'Invalid endpoint operation: ' . $_REQUEST['operation'] . '::' . $_REQUEST['mode'],
            ));
            die;
        }
        unset($params[0]);
        unset($params[1]);
        
        // Process input data
        $input = file_get_contents('php://input');
        $input = json_decode($input, true);
        
        // Merge GET, POST, and JSON body parameters
        $requestData = [];
        if (!empty($input) && is_array($input)) {
            $requestData = array_merge($_REQUEST, $params, $input);
        } else {
            $requestData = array_merge($_REQUEST, $params, $_GET, $_POST);
        }
        
        // Debug log
        error_log("Request data: " . json_encode($requestData));
        
        $this->controller::setCache($requestData);
        
        $className = 'App\\Controllers\\' . $_REQUEST['operation'];
        $cls = $className::getInstance();
        $response = $cls->{$_REQUEST['mode']}();
        
        // Set appropriate HTTP status code based on response
        if (isset($response['status']) && $response['status'] === 'error') {
            http_response_code(400); // Bad Request for client errors
        }
        
        echo json_encode($response);
    }
}

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// Initialize the API
new API();