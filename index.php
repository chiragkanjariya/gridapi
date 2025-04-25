<?php
require_once 'vendor/autoload.php';
use App\Controllers\APIController;

class API {
	private $controller = [];
	private $allowedOrigins = array();
	private $routes = [];

	/**
	 * API constructor.
	 * Initializes route patterns and handles CORS headers.
	 */
	public function __construct() {
		global $adb;

		$this->routes = [
			'/[^\/]+\/property\/?$/' => function ($params) {
				return $this->handle(array_merge(['properties', 'retrieve'], $params));
			},
			'/[^\/]+\/propertytest\/?$/' => function ($params) {
				return $this->handle(array_merge(['properties', 'newmethod'], $params));
			},
		];

		$http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';
		if (in_array($http_origin, $this->allowedOrigins)) {
			header('Access-Control-Allow-Origin: *');
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
		foreach ($this->routes as $pattern => $handler) {
			if (preg_match($pattern, $requestUri, $matches)) {
				array_shift($matches);
				$params = [];
				if (!empty($matches[0])) {
					$params = ['id' => $matches[0]];
				}
				call_user_func($handler, $params);
				return;
			}
		}
		http_response_code(404);
		echo json_encode(array(
			'response' => 'Invalid endpoint',
			'error' => true,
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
				'response' => 'Invalid endpoint',
				'error' => true,
			));
			die;
		}
		unset($params[0]);
		unset($params[1]);
		$input = file_get_contents('php://input');
		$input = json_decode($input, true);
		if (!empty($input)) {
			$this->controller::setCache(array_merge($_REQUEST, $params, $input));
		} else {
			$this->controller::setCache(array_merge($_REQUEST, $params));
		}
		$className = 'App\\Controllers\\' . $_REQUEST['operation'];
		$cls = $className::getInstance();
		$response = $cls->{$_REQUEST['mode']}();
		echo json_encode($response);
	}
}

new API();