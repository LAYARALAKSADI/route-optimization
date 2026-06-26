<?php

class RouteOptimizer {
    private $jobs;
    private $startLat;
    private $startLng;
    private $distanceCalculator;
    private $googleMapsService;
    private $optimizedRoute = [];
    private $algorithm;
    
    public function __construct($startLat, $startLng, $algorithm = 'nearest_neighbor') {
        $this->startLat = floatval($startLat);
        $this->startLng = floatval($startLng);
        $this->distanceCalculator = new DistanceCalculator();
        $this->algorithm = $algorithm;
        
        Validator::validateCoordinates($startLat, $startLng);
        
        $this->optimizedRoute = [
            'start' => [
                'name' => 'Sunquick Lanka Pvt Ltd',
                'lat' => $startLat,
                'lng' => $startLng
            ],
            'stops' => [],
            'total_distance' => 0,
            'total_time' => 0,
            'total_stops' => 0,
            'algorithm_used' => $algorithm
        ];
    }
    
    public function setJobs($jobs) {
        Validator::validateJobs($jobs);
        $this->jobs = $jobs;
        return $this;
    }
    
    public function setGoogleMapsService($service) {
        $this->googleMapsService = $service;
        return $this;
    }
    
    public function optimize($useGoogleMaps = false) {
        if (empty($this->jobs['data'])) {
            throw new Exception('No jobs to optimize');
        }
        
        $unvisited = $this->prepareJobs($this->jobs['data']);
        $uniqueJobs = $this->removeDuplicates($unvisited);
        
        if (empty($uniqueJobs)) {
            throw new Exception('No unique job locations to visit');
        }
        
        switch ($this->algorithm) {
            case '2-opt':
                $route = $this->optimizeWith2Opt($uniqueJobs);
                break;
            case 'nearest_neighbor':
            default:
                $route = $this->optimizeWithNearestNeighbor($uniqueJobs);
                break;
        }
        
        $this->optimizedRoute['stops'] = $route;
        $this->optimizedRoute['total_stops'] = count($route);
        $this->optimizedRoute['original_jobs'] = count($unvisited);
        $this->optimizedRoute['duplicates_removed'] = count($unvisited) - count($uniqueJobs);
        
        if ($useGoogleMaps && $this->googleMapsService) {
            return $this->addGoogleMapsData($route);
        }
        
        return $this->optimizedRoute;
    }
    
    private function optimizeWithNearestNeighbor($jobs) {
        $currentLat = $this->startLat;
        $currentLng = $this->startLng;
        $route = [];
        $totalDistance = 0;
        $totalTime = 0;
        $unvisited = $jobs;
        
        while (!empty($unvisited)) {
            $nearest = $this->findNearest($currentLat, $currentLng, $unvisited);
            
            if ($nearest) {
                $distance = $this->distanceCalculator->calculateDistance(
                    $currentLat, $currentLng,
                    $nearest['geo_lat'], $nearest['geo_lng']
                );
                
                $time = ($distance / 30) * 60;
                
                $route[] = [
                    'job' => $nearest,
                    'distance_from_previous' => round($distance, 2),
                    'estimated_time' => round($time, 1)
                ];
                
                $totalDistance += $distance;
                $totalTime += $time;
                
                $currentLat = $nearest['geo_lat'];
                $currentLng = $nearest['geo_lng'];
                
                $unvisited = array_filter($unvisited, function($job) use ($nearest) {
                    return $job['id'] !== $nearest['id'];
                });
            }
        }
        
        $this->optimizedRoute['total_distance'] = round($totalDistance, 2);
        $this->optimizedRoute['total_time'] = round($totalTime, 1);
        $this->optimizedRoute['algorithm_used'] = 'nearest_neighbor';
        
        return $route;
    }
    
    private function optimizeWith2Opt($jobs) {
        $route = $this->optimizeWithNearestNeighbor($jobs);
        
        $points = array_merge(
            [['lat' => $this->startLat, 'lng' => $this->startLng]],
            array_map(function($stop) {
                return ['lat' => $stop['job']['geo_lat'], 'lng' => $stop['job']['geo_lng']];
            }, $route)
        );
        
        $improved = true;
        $maxIterations = 1000;
        $iteration = 0;
        
        while ($improved && $iteration < $maxIterations) {
            $improved = false;
            $iteration++;
            
            for ($i = 0; $i < count($points) - 2; $i++) {
                for ($j = $i + 2; $j < count($points) - 1; $j++) {
                    $d1 = $this->distanceCalculator->calculateDistance(
                        $points[$i]['lat'], $points[$i]['lng'],
                        $points[$i + 1]['lat'], $points[$i + 1]['lng']
                    );
                    $d2 = $this->distanceCalculator->calculateDistance(
                        $points[$j]['lat'], $points[$j]['lng'],
                        $points[$j + 1]['lat'], $points[$j + 1]['lng']
                    );
                    
                    $nd1 = $this->distanceCalculator->calculateDistance(
                        $points[$i]['lat'], $points[$i]['lng'],
                        $points[$j]['lat'], $points[$j]['lng']
                    );
                    $nd2 = $this->distanceCalculator->calculateDistance(
                        $points[$i + 1]['lat'], $points[$i + 1]['lng'],
                        $points[$j + 1]['lat'], $points[$j + 1]['lng']
                    );
                    
                    if ($nd1 + $nd2 < $d1 + $d2) {
                        $points = array_merge(
                            array_slice($points, 0, $i + 1),
                            array_reverse(array_slice($points, $i + 1, $j - $i)),
                            array_slice($points, $j + 1)
                        );
                        $improved = true;
                        break 2;
                    }
                }
            }
        }
        
        $optimizedRoute = [];
        $totalDistance = 0;
        $totalTime = 0;
        $currentLat = $this->startLat;
        $currentLng = $this->startLng;
        
        for ($i = 1; $i < count($points); $i++) {
            $job = null;
            foreach ($jobs as $j) {
                if (abs($j['geo_lat'] - $points[$i]['lat']) < 0.0001 && 
                    abs($j['geo_lng'] - $points[$i]['lng']) < 0.0001) {
                    $job = $j;
                    break;
                }
            }
            
            if ($job) {
                $distance = $this->distanceCalculator->calculateDistance(
                    $currentLat, $currentLng,
                    $job['geo_lat'], $job['geo_lng']
                );
                
                $time = ($distance / 30) * 60;
                
                $optimizedRoute[] = [
                    'job' => $job,
                    'distance_from_previous' => round($distance, 2),
                    'estimated_time' => round($time, 1)
                ];
                
                $totalDistance += $distance;
                $totalTime += $time;
                $currentLat = $job['geo_lat'];
                $currentLng = $job['geo_lng'];
            }
        }
        
        $this->optimizedRoute['total_distance'] = round($totalDistance, 2);
        $this->optimizedRoute['total_time'] = round($totalTime, 1);
        $this->optimizedRoute['algorithm_used'] = '2-opt';
        $this->optimizedRoute['iterations'] = $iteration;
        
        return $optimizedRoute;
    }
    
    private function addGoogleMapsData($route) {
        $stops = $route;
        $previousLat = $this->startLat;
        $previousLng = $this->startLng;
        $totalDistance = 0;
        $totalTime = 0;
        
        foreach ($stops as &$stop) {
            $googleData = $this->googleMapsService->getDrivingDistance(
                $previousLat, $previousLng,
                $stop['job']['geo_lat'], $stop['job']['geo_lng']
            );
            
            if ($googleData) {
                $stop['driving_distance'] = $googleData['distance'];
                $stop['driving_duration'] = $googleData['duration'];
                $stop['distance_value'] = $googleData['distance_value'];
                $stop['duration_value'] = $googleData['duration_value'];
                
                $totalDistance += $googleData['distance_value'];
                $totalTime += $googleData['duration_value'];
            } else {
                $stop['driving_distance'] = $stop['distance_from_previous'] . ' km';
                $stop['driving_duration'] = $stop['estimated_time'] . ' mins';
                $stop['distance_value'] = $stop['distance_from_previous'] * 1000;
                $stop['duration_value'] = $stop['estimated_time'] * 60;
            }
            
            $previousLat = $stop['job']['geo_lat'];
            $previousLng = $stop['job']['geo_lng'];
        }
        
        $this->optimizedRoute['stops'] = $stops;
        $this->optimizedRoute['total_distance_km'] = round($totalDistance / 1000, 2);
        $this->optimizedRoute['total_duration'] = $this->formatDuration($totalTime);
        $this->optimizedRoute['total_duration_seconds'] = $totalTime;
        $this->optimizedRoute['used_google_maps'] = true;
        
        return $this->optimizedRoute;
    }
    
    private function prepareJobs($jobs) {
        return array_map(function($job) {
            return [
                'id' => $job['id'],
                'job_id' => $job['job_id'],
                'store_name' => $job['store_name'],
                'job_type' => $job['job_type'],
                'geo_lat' => floatval($job['geo_lat']),
                'geo_lng' => floatval($job['geo_lng']),
                'territory' => $job['territory'] ?? '',
                'vendor_id' => $job['vendor_id'] ?? ''
            ];
        }, $jobs);
    }
    
    private function removeDuplicates($jobs) {
        $seen = [];
        $unique = [];
        
        foreach ($jobs as $job) {
            $key = round($job['geo_lat'], 6) . ',' . round($job['geo_lng'], 6);
            if (!in_array($key, $seen)) {
                $seen[] = $key;
                $unique[] = $job;
            }
        }
        
        return $unique;
    }
    
    private function findNearest($lat, $lng, $jobs) {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($jobs as $job) {
            $distance = $this->distanceCalculator->calculateDistance(
                $lat, $lng,
                $job['geo_lat'], $job['geo_lng']
            );
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $job;
            }
        }
        
        return $nearest;
    }
    
    private function formatDuration($seconds) {
        if ($seconds >= 3600) {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . ' hr ' . $minutes . ' min';
        } elseif ($seconds >= 60) {
            return round($seconds / 60) . ' min';
        }
        return round($seconds) . ' sec';
    }
}
