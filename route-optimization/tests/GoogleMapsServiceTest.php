<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/GoogleMapsService.php';

class GoogleMapsServiceTest extends TestCase {
    private $service;
    
    protected function setUp(): void {
        $apiKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';
        
        if (empty($apiKey)) {
            $this->markTestSkipped('Google Maps API key not configured');
        }
        
        $this->service = new GoogleMapsService($apiKey);
    }
    
    public function testDrivingDistance() {
        $result = $this->service->getDrivingDistance(
            6.9271, 79.8612,
            7.2940094, 79.8396836
        );
        
        if ($result) {
            $this->assertArrayHasKey('distance', $result);
            $this->assertArrayHasKey('duration', $result);
            $this->assertArrayHasKey('distance_value', $result);
            $this->assertArrayHasKey('duration_value', $result);
            $this->assertGreaterThan(0, $result['distance_value']);
        } else {
            $this->markTestSkipped('Google Maps API returned null (check quota or API key)');
        }
    }
    
    public function testRateLimiting() {
        $service = new GoogleMapsService(getenv('GOOGLE_MAPS_API_KEY'), 10);
        
        $start = microtime(true);
        for ($i = 0; $i < 5; $i++) {
            $service->getDrivingDistance(6.9271, 79.8612, 7.2940094, 79.8396836);
        }
        $duration = microtime(true) - $start;
        
        
        $this->assertGreaterThan(0.4, $duration);
    }
}