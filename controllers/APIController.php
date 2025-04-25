<?php
namespace App\Controllers;

class APIController {

	public static $data = [];
	public static $instance = null;

	/**
	 * Constructor for the APIController class.
	 */
	public function __construct() {
	}

	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new APIController();
		}
		return self::$instance;
	}

	public static function setCache($data = []) {
		self::$data = $data;
	}

	/**
	 * Check if a specific operation is requested.
	 *
	 * @return bool True if the requested operation exists, false otherwise.
	 */
	public function Request() {
		if (isset($_REQUEST['operation']) && isset($_REQUEST['mode'])) {
			$className = 'App\\Controllers\\' . $_REQUEST['operation'];
			return method_exists($className, $_REQUEST['mode']);
		}
		return false;
	}

	public function Logs($methodname, $listreq, $resp, $endpoint) {
		//write logs for request
		$logname = 'logs/'.$methodname;
		$dateofcall = date('l jS \of F Y h:i:s A');
		$LogContent = "\n*****************\nDate of call $dateofcall";
		error_log($LogContent, 3, $logname.'.log');
		error_log("\nEndpoint: ".$endpoint, 3, $logname.'.log');
		error_log("\nParameters: ".json_encode($listreq), 3, $logname.'.log');
		//write logs for response
		$dateofresponse = date('l jS \of F Y h:i:s A');
		error_log("\n\nResponse: ".json_encode($resp), 3, $logname.'.log');
		error_log("\n\n".$dateofresponse."\n", 3, $logname.'.log');
	}
}