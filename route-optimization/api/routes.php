<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/JobFetcher.php';
require_once __DIR__ . '/../src/RouteOptimizer.php';
require_once __DIR__ . '/../src/DistanceCalculator.php';
require_once __DIR__ . '/../src/GoogleMapsService.php';
require_once __DIR__ . '/../src/Validator.php';


function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rateFile = __DIR__ . '/../cache/ratelimit_' . md5($ip) . '.json';
    
    if (file_exists($rateFile)) {
        $data = json_decode(file_get_contents($rateFile), true);
        $timeWindow = 60; // 1 minute
        
        if ($data && isset($data['count']) && isset($data['timestamp'])) {
            if (time() - $data['timestamp'] < $timeWindow) {
                if ($data['count'] >= 60) { // 60 requests per minute
                    sendResponse(['error' => 'Rate limit exceeded. Please try again later.'], 429);
                    exit();
                }
                $data['count']++;
            } else {
                $data['count'] = 1;
                $data['timestamp'] = time();
            }
        }
    } else {
        $data = ['count' => 1, 'timestamp' => time()];
    }
    
    file_put_contents($rateFile, json_encode($data));
}


if (APP_ENV === 'production') {
    checkRateLimit();
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


if (DEBUG_MODE) {
    error_log("API Request: [$method] action=$action");
}

try {
   
    $validActions = ['optimize', 'optimize-with-maps', 'jobs', 'health'];
    if (!in_array($action, $validActions)) {
        sendResponse([
            'error' => 'Invalid action',
            'available_actions' => $validActions
        ], 400);
    }
    
    switch ($action) {
        case 'health':
            handleHealthCheck();
            break;
            
        case 'optimize':
            handleOptimize();
            break;
            
        case 'optimize-with-maps':
            handleOptimizeWithMaps();
            break;
            
        case 'jobs':
            handleGetJobs();
            break;
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    }
    sendResponse([
        'error' => $e->getMessage(),
        'debug' => DEBUG_MODE ? [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : null
    ], 500);
}

function handleHealthCheck() {
    sendResponse([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'environment' => APP_ENV,
        'version' => '2.0.0'
    ]);
}

function handleOptimize() {
    try {
        $fetcher = new JobFetcher(API_URL, CACHE_DURATION);
        $jobs = $fetcher->fetchJobs();
        
        if (empty($jobs['data'])) {
            sendResponse([
                'status' => 'error',
                'message' => 'No jobs found to optimize'
            ], 404);
            return;
        }
        
        $algorithm = $_GET['algorithm'] ?? 'nearest_neighbor';
        if (!in_array($algorithm, ['nearest_neighbor', '2-opt'])) {
            $algorithm = 'nearest_neighbor';
        }
        
        $optimizer = new RouteOptimizer(SUNQUICK_LAT, SUNQUICK_LNG, $algorithm);
        $route = $optimizer->setJobs($jobs)->optimize(false);
        
        sendResponse([
            'status' => 'success',
            'route' => $route,
            'algorithm' => $algorithm,
            'message' => "Route optimized using {$algorithm} algorithm",
            'timestamp' => date('c')
        ]);
    } catch (Exception $e) {
        sendResponse([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

function handleOptimizeWithMaps() {
    try {
        if (!GOOGLE_MAPS_API_KEY) {
            sendResponse([
                'error' => 'Google Maps API key not configured',
                'message' => 'Please add your Google Maps API key to the .env file'
            ], 400);
            return;
        }
        
        $fetcher = new JobFetcher(API_URL, CACHE_DURATION);
        $jobs = $fetcher->fetchJobs();
        
        if (empty($jobs['data'])) {
            sendResponse([
                'status' => 'error',
                'message' => 'No jobs found to optimize'
            ], 404);
            return;
        }
        
        $googleService = new GoogleMapsService(GOOGLE_MAPS_API_KEY);
        
        $optimizer = new RouteOptimizer(SUNQUICK_LAT, SUNQUICK_LNG, 'nearest_neighbor');
        $route = $optimizer->setJobs($jobs)->setGoogleMapsService($googleService)->optimize(true);
        
        sendResponse([
            'status' => 'success',
            'route' => $route,
            'message' => 'Route optimized with Google Maps driving directions',
            'timestamp' => date('c')
        ]);
    } catch (Exception $e) {
        sendResponse([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

function handleGetJobs() {
    try {
        $fetcher = new JobFetcher(API_URL, CACHE_DURATION);
        $jobs = $fetcher->fetchJobs();
        
        $jobCount = count($jobs['data'] ?? []);
        
        $uniqueLocations = [];
        foreach ($jobs['data'] ?? [] as $job) {
            $key = round($job['geo_lat'], 6) . ',' . round($job['geo_lng'], 6);
            $uniqueLocations[$key] = true;
        }
        
        sendResponse([
            'status' => 'success',
            'jobs' => $jobs['data'] ?? [],
            'total' => $jobCount,
            'unique_locations' => count($uniqueLocations),
            'duplicates' => $jobCount - count($uniqueLocations),
            'timestamp' => date('c')
        ]);
    } catch (Exception $e) {
        sendResponse([
            'status' => 'error',
            'message' => $e->getMessage(),
            'jobs' => [],
            'total' => 0
        ], 500);
    }
}

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}