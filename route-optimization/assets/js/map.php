<?php

header('Content-Type: application/javascript');
require_once __DIR__ . '/../../config.php';
?>

const CONFIG = {
    SUNQUICK_LAT: <?php echo SUNQUICK_LAT; ?>,
    SUNQUICK_LNG: <?php echo SUNQUICK_LNG; ?>,
    GOOGLE_MAPS_API_KEY: '<?php echo GOOGLE_MAPS_API_KEY; ?>'
};


let map;
let markers = [];
let routePath;
let infoWindow;

const startPoint = {
    lat: CONFIG.SUNQUICK_LAT,
    lng: CONFIG.SUNQUICK_LNG
};

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 13,
        center: startPoint,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });
    
    infoWindow = new google.maps.InfoWindow();
    addMarker(startPoint.lat, startPoint.lng, 'Sunquick Lanka Pvt Ltd', true);
}

function addMarker(lat, lng, title, isStart = false) {
    const marker = new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: map,
        title: title,
        icon: isStart ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png' : undefined
    });
    
    markers.push(marker);
    
    google.maps.event.addListener(marker, 'click', function() {
        infoWindow.setContent(`<h6>${title}</h6>`);
        infoWindow.open(map, marker);
    });
    
    return marker;
}

