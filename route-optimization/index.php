<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Optimization - Sunquick Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <script>
        const CONFIG = {
            SUNQUICK_LAT: <?php echo SUNQUICK_LAT; ?>,
            SUNQUICK_LNG: <?php echo SUNQUICK_LNG; ?>,
            GOOGLE_MAPS_API_KEY: '<?php echo GOOGLE_MAPS_API_KEY; ?>',
            // FIX: Use relative path for API
            API_BASE_URL: '/api/routes.php',
            USE_OPENSTREETMAP: <?php echo USE_OPENSTREETMAP ? 'true' : 'false'; ?>
        };
    </script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 sidebar">
                <div class="sidebar-header">
                    <h1>Route Optimization</h1>
                    <div class="subtitle">Sunquick Lanka Pvt Ltd</div>
                </div>
                
                <div class="sidebar-content">
                    <div class="start-card">
                        <div class="card-title">
                            Sunquick Lanka
                            <span class="badge-start">START</span>
                        </div>
                        <div class="card-text">
                            <?php echo SUNQUICK_LAT; ?>, <?php echo SUNQUICK_LNG; ?>
                        </div>
                        <div class="job-count">
                            Jobs: <span class="count-number" id="totalJobs">0</span>
                            <span class="map-badge osm">OpenStreetMap</span>
                        </div>
                    </div>
                    
                    <div class="route-summary-card" id="routeSummary">
                        <div class="summary-title">Route Summary</div>
                        <div class="summary-stats" id="summaryContent"></div>
                    </div>
                    
                    <div class="stops-list-wrapper">
                        <div class="stops-title">
                            Visit Order
                            <span class="stops-count" id="stopsCount">0 Stops</span>
                        </div>
                        <div id="stopsList"></div>
                    </div>
                    
                    <button id="optimizeBtn" class="btn btn-success w-100 mb-2">
                        🚀 Optimize Route
                    </button>
                    <button id="optimizeWithMapsBtn" class="btn btn-info w-100 mb-2" style="display:none;">
                        🗺️ Optimize with Google Maps
                    </button>
                    <button id="refreshBtn" class="btn btn-secondary w-100">
                        🔄 Refresh Jobs
                    </button>
                    
                    <div id="routeFooter" style="display:none;"></div>
                </div>
            </div>
            
            <div class="col-md-8 p-0">
                <div id="map"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <?php if (!USE_OPENSTREETMAP): ?>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&callback=initMap" async defer></script>
    <?php endif; ?>
    
    <script src="assets/js/map.js"></script>
</body>
</html>