<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/JobFetcher.php';

class JobFetcherTest extends TestCase {
    private $fetcher;
    
    protected function setUp(): void {
        $this->fetcher = new JobFetcher('https://service-connect.free.beeceptor.com/tickets', 3600);
    }
    
    public function testFetchJobs() {
        $jobs = $this->fetcher->fetchJobs();
        
        $this->assertIsArray($jobs);
        $this->assertArrayHasKey('data', $jobs);
        $this->assertIsArray($jobs['data']);
    }
    
    public function testCacheCreation() {
        $cacheFile = __DIR__ . '/../cache/jobs.json';
        
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        
        $jobs = $this->fetcher->fetchJobs();
        
        $this->assertFileExists($cacheFile);
        $this->assertNotEmpty(file_get_contents($cacheFile));
    }
    
    public function testCacheValid() {
        $jobs1 = $this->fetcher->fetchJobs();
        $jobs2 = $this->fetcher->fetchJobs(); 
        
        $this->assertEquals($jobs1, $jobs2);
    }
    
    public function testForceRefresh() {
        $jobs1 = $this->fetcher->fetchJobs();
        $jobs2 = $this->fetcher->fetchJobs(true); 
        
        $this->assertEquals($jobs1, $jobs2);
    }
}