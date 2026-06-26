<?php

class JobFetcher {
    private $apiUrl;
    private $cacheFile;
    private $cacheDuration;
    private $logger;
    
    public function __construct($apiUrl, $cacheDuration = 3600) {
        $this->apiUrl = $apiUrl;
        $this->cacheFile = __DIR__ . '/../cache/jobs.json';
        $this->cacheDuration = $cacheDuration;
        $this->logger = function($message, $level = 'info') {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("[JobFetcher][$level] $message");
            }
        };
    }
    
    public function fetchJobs($forceRefresh = false) {
        if (!$forceRefresh && $this->isCacheValid()) {
            $this->log('Using cached jobs data');
            return $this->getCachedJobs();
        }
        
        $this->log('Fetching jobs from API: ' . $this->apiUrl);
        
        $data = $this->fetchFromApi();
        
        if ($data && isset($data['data'])) {
            $this->cacheJobs($data);
            $this->log('Successfully fetched ' . count($data['data']) . ' jobs');
            return $data;
        }
        
        $this->log('Failed to fetch from API, falling back to cache', 'error');
        
        if ($this->hasCachedJobs()) {
            return $this->getCachedJobs();
        }
        
        throw new Exception('Unable to fetch jobs from API and no valid cache available.');
    }
    
    private function fetchFromApi() {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($this->apiUrl, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['data'])) {
                return $data;
            }
            
            $this->log('Invalid JSON response: ' . json_last_error_msg(), 'error');
        } else {
            $this->log('HTTP request failed: ' . error_get_last()['message'] ?? 'Unknown error', 'error');
        }
        
        return null;
    }
    
    private function isCacheValid() {
        if (!$this->hasCachedJobs()) {
            return false;
        }
        
        $cacheAge = time() - filemtime($this->cacheFile);
        $isValid = $cacheAge < $this->cacheDuration;
        
        $this->log("Cache age: {$cacheAge}s, Valid: " . ($isValid ? 'yes' : 'no'));
        
        return $isValid;
    }
    
    private function hasCachedJobs() {
        return file_exists($this->cacheFile);
    }
    
    private function getCachedJobs() {
        $content = file_get_contents($this->cacheFile);
        $data = json_decode($content, true);
        
        if ($data && isset($data['data'])) {
            return $data;
        }
        
        throw new Exception('Invalid cache data');
    }
    
    private function cacheJobs($data) {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
        $this->log('Cached ' . count($data['data']) . ' jobs');
    }
    
    private function log($message, $level = 'info') {
        if ($this->logger instanceof Closure) {
            call_user_func($this->logger, $message, $level);
        }
    }
}