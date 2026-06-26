<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/DistanceCalculator.php';
require_once __DIR__ . '/../src/RouteOptimizer.php';
require_once __DIR__ . '/../src/Validator.php';

class RouteOptimizerTest extends TestCase {
    private $optimizer;
    private $sampleJobs;
    
    protected function setUp(): void {
        $this->optimizer = new RouteOptimizer(6.9271, 79.8612);
        $this->sampleJobs = [
            'data' => [
                [
                    'id' => '1',
                    'job_id' => 'JR#001',
                    'store_name' => 'Store 1',
                    'geo_lat' => '7.2940094',
                    'geo_lng' => '79.8396836',
                    'job_type' => 'Test'
                ],
                [
                    'id' => '2',
                    'job_id' => 'JR#002',
                    'store_name' => 'Store 2',
                    'geo_lat' => '7.2455846',
                    'geo_lng' => '79.8418470',
                    'job_type' => 'Test'
                ],
                [
                    'id' => '3',
                    'job_id' => 'JR#003',
                    'store_name' => 'Store 3',
                    'geo_lat' => '7.2373457',
                    'geo_lng' => '79.8729596',
                    'job_type' => 'Test'
                ]
            ]
        ];
    }
    
    public function testRouteOptimization() {
        $route = $this->optimizer->setJobs($this->sampleJobs)->optimize(false);
        
        $this->assertArrayHasKey('stops', $route);
        $this->assertArrayHasKey('total_distance', $route);
        $this->assertArrayHasKey('total_stops', $route);
        $this->assertArrayHasKey('algorithm_used', $route);
        $this->assertEquals(3, count($route['stops']));
        $this->assertGreaterThan(0, $route['total_distance']);
    }
    
    public function testDuplicateRemoval() {
        $jobsWithDuplicates = [
            'data' => array_merge($this->sampleJobs['data'], [
                [
                    'id' => '4',
                    'job_id' => 'JR#004',
                    'store_name' => 'Store 1',
                    'geo_lat' => '7.2940094', 
                    'geo_lng' => '79.8396836',
                    'job_type' => 'Test'
                ]
            ])
        ];
        
        $route = $this->optimizer->setJobs($jobsWithDuplicates)->optimize(false);
        
        $this->assertEquals(3, count($route['stops'])); 
        $this->assertEquals(2, $route['duplicates_removed']); 
    }
    
    public function testEmptyJobs() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No jobs to optimize');
        $this->optimizer->setJobs(['data' => []]);
    }
    
    public function testInvalidCoordinates() {
        $invalidJobs = [
            'data' => [
                [
                    'id' => '1',
                    'job_id' => 'JR#001',
                    'store_name' => 'Store 1',
                    'geo_lat' => '1000', 
                    'geo_lng' => '79.8396836',
                    'job_type' => 'Test'
                ]
            ]
        ];
        
        $this->expectException(InvalidArgumentException::class);
        $this->optimizer->setJobs($invalidJobs);
    }
    
    public function test2OptOptimization() {
        $optimizer = new RouteOptimizer(6.9271, 79.8612, '2-opt');
        $route = $optimizer->setJobs($this->sampleJobs)->optimize(false);
        
        $this->assertEquals('2-opt', $route['algorithm_used']);
        $this->assertArrayHasKey('iterations', $route);
        $this->assertGreaterThan(0, $route['iterations']);
    }
    
    public function testRouteDistanceOrdering() {
        $route = $this->optimizer->setJobs($this->sampleJobs)->optimize(false);
        $stops = $route['stops'];
      
        for ($i = 1; $i < count($stops); $i++) {
            $prevDist = $stops[$i - 1]['distance_from_previous'];
            $currDist = $stops[$i]['distance_from_previous'];
            
            $this->assertGreaterThan(0, $prevDist);
            $this->assertGreaterThan(0, $currDist);
        }
    }
}