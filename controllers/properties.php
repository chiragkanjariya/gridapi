<?php
namespace App\Controllers;

use App\Controllers\APIController;

class routes extends APIController {
	// Error codes used in session/service validation
	private $codes = [
		'NOT_FOUND' => '',
		//...
	];

	public static $instance = null;

	public function __construct() {
	}

	/**
	 * Singleton pattern: returns the single instance of this class
	 */
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new routes();
		}
		return self::$instance;
	}

	/**
	 * @return array
	 */
	public function retrieve() {
	}

	public function newmethod() {
	}

	public function anothermethod() {
	}
}