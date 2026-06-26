<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/DistanceCalculator.php';
require_once __DIR__ . '/../src/Validator.php';

class DistanceCalculatorTest extends TestCase {
    private $calculator;
    
    protected function setUp(): void {
        $this->calculator = new DistanceCalculator();
    }
    
    public function testDistanceCalculation() {
        
        $distance = $this->calculator->calculateDistance(
            6.9271, 79.8612, 
            7.2940094, 79.8396836 
        );
        
        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(100, $distance); 
    }
    
    public function testZeroDistance() {
        $distance = $this->calculator->calculateDistance(
            6.9271, 79.8612,
            6.9271, 79.8612
        );
        
        $this->assertEquals(0, $distance);
    }
    
    public function testMilesUnit() {
        $calculator = new DistanceCalculator('miles');
        $distance = $calculator->calculateDistance(
            6.9271, 79.8612,
            7.2940094, 79.8396836
        );
        
        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }
    
   
    public function testCoordinateValidation($lat, $lng, $expected) {
        if ($expected) {
            $this->assertTrue(Validator::validateCoordinates($lat, $lng));
        } else {
            $this->expectException(InvalidArgumentException::class);
            Validator::validateCoordinates($lat, $lng);
        }
    }
    
    public function coordinateProvider() {
        return [
            [0, 0, true],
            [90, 180, true],
            [-90, -180, true],
            [91, 0, false],
            [-91, 0, false],
            [0, 181, false],
            [0, -181, false]
        ];
    }
}
