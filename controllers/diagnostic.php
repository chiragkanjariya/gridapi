<?php
namespace App\Controllers;

use App\Controllers\APIController;
use App\Services\AttomAPIService;

class diagnostic extends APIController {
    private static $instance = null;
    private $attomService;

    public function __construct() {
        parent::__construct();
        $this->attomService = AttomAPIService::getInstance();
    }

    /**
     * Singleton pattern: returns the single instance of this class
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new diagnostic();
        }
        return self::$instance;
    }

    /**
     * Test API connectivity
     */
    public function test() {
        // Get API key
        $apiKey = getenv('ATTOM_API_KEY') ?: 'YOUR_API_KEY_HERE';
        
        // Construct test URL
        $url = 'https://api.developer.attomdata.com/propertyapi/v1/property/basicprofile';
        $params = ['attomId' => '123456789'];
        $url .= '?' . http_build_query($params);
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'apikey: ' . $apiKey
        ]);
        
        // Add verbose debugging
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = curl_getinfo($ch);
        
        // Get verbose info
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        
        // Check for errors
        $curlError = '';
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
        }
        
        curl_close($ch);
        
        // Return diagnostic information
        return [
            'status' => ($httpCode < 400) ? 'success' : 'error',
            'code' => $httpCode,
            'api_url' => $url,
            'api_key_length' => strlen($apiKey),
            'api_key_preview' => substr($apiKey, 0, 5) . '...' . substr($apiKey, -5),
            'curl_error' => $curlError,
            'http_info' => $httpInfo,
            'verbose_log' => $verboseLog,
            'response_preview' => substr($response, 0, 500) . (strlen($response) > 500 ? "..." : ""),
        ];
    }
}