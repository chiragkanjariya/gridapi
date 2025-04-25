<?php
namespace App\Services;

class AttomAPIService {
    private $apiKey;
    // Update the base URL to match the working endpoint
    private $baseUrl = 'https://api.gateway.attomdata.com/propertyapi/v1.0.0';
    private static $instance = null;

    public function __construct() {
        // Load API key from environment or configuration
        $this->apiKey = getenv('ATTOM_API_KEY') ?: 'YOUR_API_KEY_HERE';
    }

    /**
     * Singleton pattern: returns the single instance of this class
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new AttomAPIService();
        }
        return self::$instance;
    }

    /**
     * Makes API request to ATTOM Data API
     * 
     * @param string $endpoint The API endpoint
     * @param array $params Query parameters
     * @return array Response data
     */
    public function request($endpoint, $params = []) {
        $url = $this->baseUrl . $endpoint;
        
        // Filter out middleware-specific parameters
        $apiParams = array_filter($params, function($key) {
            return !in_array($key, ['operation', 'mode']);
        }, ARRAY_FILTER_USE_KEY);
        
        // Convert attomId parameter to lowercase if it exists
        if (isset($apiParams['attomId'])) {
            $apiParams['attomid'] = $apiParams['attomId'];
            unset($apiParams['attomId']);
        }
        
        // Add query parameters if provided
        if (!empty($apiParams)) {
            $url .= '?' . http_build_query($apiParams);
        }

        // Log the request URL for debugging
        error_log("Making ATTOM API request to: " . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'apikey: ' . $this->apiKey,
            'accept: application/json'  // Duplicate header as shown in your working example
        ]);
        
        // Add verbose debugging
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = curl_getinfo($ch);
        
        // Log verbose output
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        error_log("Curl verbose output: " . $verboseLog);
        
        // Log detailed HTTP info
        error_log("HTTP Info: " . json_encode($httpInfo));
        
        // Log curl errors if any
        if (curl_errno($ch)) {
            error_log("Curl error: " . curl_error($ch));
        }
        
        curl_close($ch);

        // Log response for debugging
        error_log("API Response Code: " . $httpCode);
        error_log("API Response Body: " . substr($response, 0, 1000) . (strlen($response) > 1000 ? "..." : ""));

        if ($httpCode >= 400) {
            return [
                'status' => 'error',
                'code' => $httpCode,
                'message' => 'API request failed',
                'raw_response' => $response,
                'request_url' => $url
            ];
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => 'error',
                'code' => $httpCode,
                'message' => 'Failed to parse API response',
                'json_error' => json_last_error_msg(),
                'raw_response' => $response
            ];
        }
        
        return $decoded;
    }

    /**
     * Get properties by zip code
     */
    public function getPropertyAddress($params) {
        return $this->request('/property/address', $params);
    }

    /**
     * Get basic property information
     */
    public function getPropertyBasicprofile($params) {
        return $this->request('/property/basicprofile', $params);
    }

    /**
     * Get property with building permits
     */
    public function getPropertyBuildingpermits($params) {
        return $this->request('/property/buildingpermits', $params);
    }

    /**
     * Get detailed property information
     */
    public function getPropertyDetail($params) {
        return $this->request('/property/detail', $params);
    }

    /**
     * Get property details with owner information
     */
    public function getPropertyDetailowner($params) {
        return $this->request('/property/detailowner', $params);
    }

    /**
     * Get expanded property profile
     */
    public function getPropertyExpandedprofile($params) {
        return $this->request('/property/expandedprofile', $params);
    }

    /**
     * Get properties by ID
     */
    public function getPropertyById($params) {
        return $this->request('/property/id', $params);
    }

    /**
     * Get property snapshots
     */
    public function getPropertySnapshot($params) {
        return $this->request('/property/snapshot', $params);
    }
}