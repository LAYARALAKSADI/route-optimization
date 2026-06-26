<?php

spl_autoload_register(function ($class) {
    $classMap = [
        'DistanceCalculator' => __DIR__ . '/src/DistanceCalculator.php',
        'GoogleMapsService' => __DIR__ . '/src/GoogleMapsService.php',
        'JobFetcher' => __DIR__ . '/src/JobFetcher.php',
        'RouteOptimizer' => __DIR__ . '/src/RouteOptimizer.php',
        'Validator' => __DIR__ . '/src/Validator.php',
    ];
    
    if (isset($classMap[$class])) {
        require_once $classMap[$class];
        return true;
    }
    
    
    $file = __DIR__ . '/src/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    return false;
});