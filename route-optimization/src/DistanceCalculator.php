<?php

class DistanceCalculator {
    const EARTH_RADIUS_KM = 6371;
    const EARTH_RADIUS_MILES = 3959;
    
    private $unit;
    
    public function __construct($unit = 'km') {
        $this->unit = $unit;
    }
    
    public function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $lat1 = floatval($lat1);
        $lng1 = floatval($lng1);
        $lat2 = floatval($lat2);
        $lng2 = floatval($lng2);
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLng / 2) * sin($dLng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        $radius = $this->unit === 'miles' ? self::EARTH_RADIUS_MILES : self::EARTH_RADIUS_KM;
        
        return $radius * $c;
    }
    
    public function getDrivingDistance($lat1, $lng1, $lat2, $lng2, $apiKey) {
        if (empty($apiKey)) {
            return null;
        }
        
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?" . http_build_query([
            'origins' => "{$lat1},{$lng1}",
            'destinations' => "{$lat2},{$lng2}",
            'key' => $apiKey,
            'units' => $this->unit === 'miles' ? 'imperial' : 'metric'
        ]);
        
        $response = $this->makeRequest($url);
        
        if ($response && $response['status'] === 'OK') {
            $element = $response['rows'][0]['elements'][0] ?? null;
            if ($element && $element['status'] === 'OK') {
                return [
                    'distance' => $element['distance']['text'],
                    'distance_value' => $element['distance']['value'],
                    'duration' => $element['duration']['text'],
                    'duration_value' => $element['duration']['value']
                ];
            }
        }
        
        return null;
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