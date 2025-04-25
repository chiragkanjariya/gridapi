<?php
namespace App\Services;

class AttomAPIService {
    private $apiKey;
    // Update the base URL to match ATTOM's actual API endpoint structure
    private $baseUrl = 'https://api.gateway.attomdata.com/api/v1';
    private static $instance = null;

    public function __construct() {
        // Load API key from environment or configuration
        $this->apiKey = getenv('ATTOM_API_KEY') ?: 'YOUR_API_KEY_HERE';
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
        
        // Add query parameters if provided
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Log the request URL for debugging
        error_log("Making request to: " . $url);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'apikey: ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Log curl errors if any
        if (curl_errno($ch)) {
            error_log("Curl error: " . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode >= 400) {
            return [
                'status' => 'error',
                'code' => $httpCode,
                'message' => 'API request failed',
                'raw_response' => $response
            ];
        }

        return json_decode($response, true);
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
    public function getPropertyId($params) {
        return $this->request('/property/id', $params);
    }

    /**
     * Get property snapshots
     */
    public function getPropertySnapshot($params) {
        return $this->request('/property/snapshot', $params);
    }
}