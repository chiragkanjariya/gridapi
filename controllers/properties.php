<?php
namespace App\Controllers;

use App\Controllers\APIController;
use App\Services\AttomAPIService;

class properties extends APIController {
    // Error codes used in session/service validation
    private $codes = [
        'NOT_FOUND' => 'Property not found',
        'INVALID_PARAMS' => 'Invalid parameters provided',
        'API_ERROR' => 'Error communicating with property data service'
    ];

    public static $instance = null;
    private $attomService;

    public function __construct() {
        $this->attomService = AttomAPIService::getInstance();
    }

    /**
     * Singleton pattern: returns the single instance of this class
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new properties();
        }
        return self::$instance;
    }

    /**
     * Common method to process API requests and handle errors
     */
    private function processRequest($method, $required = []) {
        $data = self::$data;
        
        // Validate required parameters
        foreach ($required as $param) {
            if (!isset($data[$param]) || empty($data[$param])) {
                return [
                    'status' => 'error',
                    'message' => $this->codes['INVALID_PARAMS'],
                    'missing' => $param
                ];
            }
        }
        
        // Log the request
        $this->Logs($method, $data, null, '/property/' . $method);
        
        // Call the appropriate service method
        $response = $this->attomService->{"getProperty" . ucfirst($method)}($data);
        
        // Log the response
        $this->Logs($method, $data, $response, '/property/' . $method);
        
        return $response;
    }

    /**
     * Get properties by address
     * @return array
     */
    public function address() {
        return $this->processRequest('Address', ['postalcode']);
    }
    
    /**
     * Get basic property profile
     * @return array
     */
    public function basicprofile() {
        return $this->processRequest('BasicProfile', ['attomId']);
    }
    
    /**
     * Get property with building permits
     * @return array
     */
    public function buildingpermits() {
        return $this->processRequest('BuildingPermits', ['attomId']);
    }
    
    /**
     * Get property details
     * @return array
     */
    public function detail() {
        return $this->processRequest('Detail', ['attomId']);
    }
    
    /**
     * Get property details with owner information
     * @return array
     */
    public function detailowner() {
        return $this->processRequest('DetailOwner', ['attomId']);
    }
    
    /**
     * Get expanded property profile
     * @return array
     */
    public function expandedprofile() {
        return $this->processRequest('ExpandedProfile', ['attomId']);
    }
    
    /**
     * Get properties by ID
     * @return array
     */
    public function id() {
        // This endpoint can accept various parameters
        return $this->processRequest('ById', []);
    }
    
    /**
     * Get property snapshots in a zip code
     * @return array
     */
    public function snapshot() {
        return $this->processRequest('Snapshot', ['postalcode']);
    }

    /**
     * Legacy method - redirects to appropriate new method
     * @return array
     */
    public function retrieve() {
        // Map to one of the new methods based on parameters
        if (isset(self::$data['postalcode'])) {
            return $this->address();
        } elseif (isset(self::$data['attomId'])) {
            return $this->detail();
        } else {
            return [
                'status' => 'error',
                'message' => $this->codes['INVALID_PARAMS']
            ];
        }
    }
}