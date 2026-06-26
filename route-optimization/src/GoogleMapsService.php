<?php

class GoogleMapsService {
    private $apiKey;
    private $cache;
    private $rateLimit;
    private $lastRequestTime = 0;
    
    public function __construct($apiKey, $rateLimit = 10) {
        $this->apiKey = $apiKey;
        $this->rateLimit = $rateLimit; // requests per second
        $this->cache = [];
    }
    
    public function getDrivingDistance($lat1, $lng1, $lat2, $lng2) {
        if (empty($this->apiKey)) {
            throw new Exception('Google Maps API key is required');
        }
        
        $cacheKey = $this->getCacheKey($lat1, $lng1, $lat2, $lng2);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
   
        $this->applyRateLimit();
        
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?" . http_build_query([
            'origins' => "{$lat1},{$lng1}",
            'destinations' => "{$lat2},{$lng2}",
            'key' => $this->apiKey,
            'units' => 'metric'
        ]);
        
        $response = $this->makeRequest($url);
        
        if ($response && isset($response['status']) && $response['status'] === 'OK') {
            $element = $response['rows'][0]['elements'][0] ?? null;
            if ($element && isset($element['status']) && $element['status'] === 'OK') {
                $result = [
                    'distance' => $element['distance']['text'],
                    'distance_value' => $element['distance']['value'],
                    'duration' => $element['duration']['text'],
                    'duration_value' => $element['duration']['value']
                ];
                
                
                $this->cache[$cacheKey] = $result;
                
                return $result;
            }
        }
        
       
        $errorMsg = $response['error_message'] ?? $response['status'] ?? 'Unknown error';
        error_log("Google Maps API error: " . $errorMsg);
        
        return null;
    }
    
    private function getCacheKey($lat1, $lng1, $lat2, $lng2) {
        return md5(round($lat1, 6) . ',' . round($lng1, 6) . ',' . 
                   round($lat2, 6) . ',' . round($lng2, 6));
    }
    
    private function applyRateLimit() {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;
        
        if ($timeSinceLastRequest < (1 / $this->rateLimit)) {
            $sleepTime = (1 / $this->rateLimit) - $timeSinceLastRequest;
            usleep($sleepTime * 1000000);
        }
        
        $this->lastRequestTime = microtime(true);
    }
    
    private function makeRequest($url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            return json_decode($response, true);
        }
        
        return null;
    }
}