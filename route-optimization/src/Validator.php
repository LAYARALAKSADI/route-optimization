<?php

class Validator {
    public static function validateCoordinates($lat, $lng) {
        $lat = floatval($lat);
        $lng = floatval($lng);
        
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException('Invalid latitude: ' . $lat);
        }
        
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException('Invalid longitude: ' . $lng);
        }
        
        return true;
    }
    
    public static function validateJobs($jobs) {
        if (empty($jobs) || !isset($jobs['data'])) {
            throw new InvalidArgumentException('Invalid job data structure');
        }
        
        if (!is_array($jobs['data'])) {
            throw new InvalidArgumentException('Jobs data must be an array');
        }
        
        foreach ($jobs['data'] as $index => $job) {
            if (!isset($job['geo_lat']) || !isset($job['geo_lng'])) {
                throw new InvalidArgumentException("Job at index {$index} missing coordinates");
            }
            
            self::validateCoordinates($job['geo_lat'], $job['geo_lng']);
        }
        
        return true;
    }
}