let map;
let markers = [];
let routePath;
let infoWindow;
let isOpenStreetMap = CONFIG.USE_OPENSTREETMAP;
let allJobsData = [];
let routeData = null;
let routePolylines = [];

const startPoint = {
    lat: CONFIG.SUNQUICK_LAT,
    lng: CONFIG.SUNQUICK_LNG
};

function initMap() {
    if (isOpenStreetMap) {
        initOpenStreetMap();
    } else {
        initGoogleMap();
    }
}

function initOpenStreetMap() {
    map = L.map('map', {
        center: [startPoint.lat, startPoint.lng],
        zoom: 13,
        zoomControl: true
    });
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);
    
    L.control.scale().addTo(map);
 
    addMarkerOpenStreetMap(startPoint.lat, startPoint.lng, '🏢 Sunquick Lanka Pvt Ltd', true);
    
    map.on('click', function(e) {
        console.log('Map clicked at:', e.latlng);
    });
}

function initGoogleMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 13,
        center: startPoint,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true
    });
    
    infoWindow = new google.maps.InfoWindow();

    addMarkerGoogle(startPoint.lat, startPoint.lng, '🏢 Sunquick Lanka Pvt Ltd', true);
}

function addMarkerOpenStreetMap(lat, lng, title, isStart = false) {
    const icon = L.divIcon({
        className: isStart ? 'marker-start' : 'marker-stop',
        html: isStart ? 
            `<div style="background:#00C853;color:white;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:16px;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.3);">🏢</div>` :
            `<div style="background:#FF6B35;color:white;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:14px;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.3);">📍</div>`,
        iconSize: [36, 36],
        iconAnchor: [18, 36],
        popupAnchor: [0, -36]
    });
    
    const marker = L.marker([lat, lng], { icon }).addTo(map);
    
    const popupContent = `
        <div style="padding:8px;">
            <strong style="color:#1F2937;font-size:14px;">${title}</strong>
            <div style="color:#6B7280;font-size:11px;margin-top:4px;">
                📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>
        </div>
    `;
    
    marker.bindPopup(popupContent);
    markers.push(marker);
    return marker;
}

function addMarkerGoogle(lat, lng, title, isStart = false) {
    const marker = new google.maps.Marker({
        position: { lat: parseFloat(lat), lng: parseFloat(lng) },
        map: map,
        title: title,
        icon: isStart ? {
            url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
            scaledSize: new google.maps.Size(40, 40)
        } : {
            url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
            scaledSize: new google.maps.Size(32, 32)
        }
    });
    
    markers.push(marker);
    
    google.maps.event.addListener(marker, 'click', function() {
        infoWindow.setContent(`<h6>${title}</h6>`);
        infoWindow.open(map, marker);
    });
    
    return marker;
}

function addMarker(lat, lng, title, isStart = false) {
    if (isOpenStreetMap) {
        return addMarkerOpenStreetMap(lat, lng, title, isStart);
    } else {
        return addMarkerGoogle(lat, lng, title, isStart);
    }
}

function drawRoute(route) {
    console.log('drawRoute called with:', route);

    clearRoute();
    
    const latlngs = [];
    latlngs.push([startPoint.lat, startPoint.lng]);
    
    route.stops.forEach(stop => {
        latlngs.push([
            parseFloat(stop.job.geo_lat),
            parseFloat(stop.job.geo_lng)
        ]);
    });
    
    if (isOpenStreetMap) {
        drawRouteOpenStreetMap(latlngs, route);
    } else {
        drawRouteGoogle(latlngs, route);
    }
 
    displayRouteSummary(route);
}

function drawRouteOpenStreetMap(latlngs, route) {
    routePath = L.polyline(latlngs, {
        color: '#FF6B35',
        weight: 5,
        opacity: 0.8,
        lineJoin: 'round'
    }).addTo(map);
    
    routePolylines.push(routePath);
    
    const dashedPath = L.polyline(latlngs, {
        color: '#FFFFFF',
        weight: 2,
        opacity: 0.3,
        dashArray: '5, 10',
        lineJoin: 'round'
    }).addTo(map);
    
    routePolylines.push(dashedPath);
   
    route.stops.forEach((stop, index) => {
        const position = [
            parseFloat(stop.job.geo_lat),
            parseFloat(stop.job.geo_lng)
        ];
        
        const orderIcon = L.divIcon({
            className: 'order-label',
            html: `<div style="background:#FF6B35;color:white;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:13px;border:3px solid white;box-shadow:0 2px 10px rgba(0,0,0,0.25);">${index + 1}</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
        
        const marker = L.marker(position, { icon: orderIcon }).addTo(map);
        routePolylines.push(marker);
       
        const job = stop.job;
        const popupContent = `
            <div style="padding:8px;max-width:200px;">
                <strong style="color:#1F2937;font-size:14px;">#${index + 1} ${job.store_name}</strong>
                <div style="color:#6B7280;font-size:11px;margin-top:4px;">
                     ${job.job_id} - ${job.job_type}
                </div>
                <div style="color:#6B7280;font-size:10px;margin-top:2px;">
                    📍 ${job.geo_lat}, ${job.geo_lng}
                </div>
                ${stop.driving_distance ? `<div style="color:#FF6B35;font-size:11px;margin-top:4px;">🚗 ${stop.driving_distance}</div>` : ''}
            </div>
        `;
        
        marker.bindPopup(popupContent);
    });
    
    const bounds = L.latLngBounds(latlngs);
    map.fitBounds(bounds, { padding: [50, 50] });
}

function drawRouteGoogle(latlngs, route) {
    const path = latlngs.map(p => new google.maps.LatLng(p[0], p[1]));
    
    routePath = new google.maps.Polyline({
        path: path,
        geodesic: true,
        strokeColor: '#FF6B35',
        strokeOpacity: 0.9,
        strokeWeight: 4
    });
    
    routePath.setMap(map);
    routePolylines.push(routePath);
    
    const bounds = new google.maps.LatLngBounds();
    path.forEach(point => bounds.extend(point));
    map.fitBounds(bounds);
}

function clearRoute() {
    routePolylines.forEach(item => {
        if (isOpenStreetMap) {
            map.removeLayer(item);
        } else {
            if (item.setMap) {
                item.setMap(null);
            }
        }
    });
    routePolylines = [];
    routePath = null;
}

function displayRouteSummary(route) {
    console.log('displayRouteSummary called with:', route);
    
    const summaryContent = document.getElementById('summaryContent');
    const stopsList = document.getElementById('stopsList');
    const summaryCard = document.getElementById('routeSummary');
    const stopsCount = document.getElementById('stopsCount');
    const footerContainer = document.getElementById('routeFooter');
    
    if (!summaryContent || !stopsList || !summaryCard) {
        console.error('Required elements not found!');
        return;
    }
    
    summaryCard.classList.remove('d-none');
    summaryCard.classList.add('visible');
    
    const uniqueLocations = new Set();
    const jobIds = new Set();
    const locationJobs = {};
    
    allJobsData.forEach(job => {
        const key = job.geo_lat + ',' + job.geo_lng;
        uniqueLocations.add(key);
        jobIds.add(job.id);
        
        if (!locationJobs[key]) {
            locationJobs[key] = [];
        }
        locationJobs[key].push(job);
    });
    
    const totalJobs = jobIds.size;
    const uniqueCount = uniqueLocations.size;
    const duplicatesRemoved = totalJobs - uniqueCount;
    
    stopsCount.textContent = route.total_stops + ' Stops';
    
   
    let totalTimeDisplay = route.total_time || 0;
    let timeUnit = 'min';
    if (totalTimeDisplay >= 60) {
        totalTimeDisplay = Math.round(totalTimeDisplay / 60 * 10) / 10;
        timeUnit = 'hr';
    }
    
    summaryContent.innerHTML = `
        <div class="summary-stats">
            <div class="stat-item highlight">
                <span class="stat-label">Jobs Received</span>
                <span class="stat-value">${totalJobs}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Unique Locations</span>
                <span class="stat-value">${uniqueCount}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Duplicates Removed</span>
                <span class="stat-value">${duplicatesRemoved}</span>
            </div>
            <div class="stat-item highlight">
                <span class="stat-label">Total Distance</span>
                <span class="stat-value">${route.total_distance} <span class="unit">km</span></span>
            </div>
            <div class="stat-item highlight">
                <span class="stat-label">Total Time</span>
                <span class="stat-value">${totalTimeDisplay} <span class="unit">${timeUnit}</span></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Algorithm</span>
                <span class="stat-value stat-value-small">${route.algorithm_used || 'Nearest Neighbor'}</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Map Provider</span>
                <span class="stat-value stat-value-small">${isOpenStreetMap ? 'OpenStreetMap' : 'Google Maps'}</span>
            </div>
        </div>
    `;
   
    let html = '';
    
    html += `
        <div class="start-point-item">
            <span class="start-icon">🏁</span>
            <div>
                <div class="start-label">Start Point</div>
                <div class="start-sub">Sunquick Lanka Pvt Ltd</div>
                <div style="color:#6B7280;font-size:10px;margin-top:2px;">
                    📍 ${startPoint.lat}, ${startPoint.lng}
                </div>
            </div>
            <span class="start-badge">START</span>
        </div>
    `;
    
    html += '<div style="margin-top:8px;">';
    
    route.stops.forEach((stop, index) => {
        const job = stop.job;
        const key = job.geo_lat + ',' + job.geo_lng;
        const relatedJobs = locationJobs[key] || [job];
        const distanceText = stop.driving_distance || stop.distance_from_previous + ' km';
        
        const jobNumbers = relatedJobs.map(j => j.job_id).join(', ');
        const jobTypes = relatedJobs.map(j => j.job_type).join(' / ');
        
        // Format time for display
        let timeDisplay = stop.estimated_time || 0;
        let timeText = timeDisplay + ' min';
        if (timeDisplay >= 60) {
            timeDisplay = Math.round(timeDisplay / 60 * 10) / 10;
            timeText = timeDisplay + ' hr';
        }
        
        html += `
            <div style="display:flex;align-items:center;gap:12px;padding:8px 14px;border-bottom:1px solid #F0F2F5;transition:background 0.2s;" 
                 onmouseover="this.style.background='#FFF0EB'" 
                 onmouseout="this.style.background='transparent'">
                <div style="background:#FF6B35;color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;">${index + 1}</div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;color:#1F2937;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${job.store_name}</div>
                    <div style="color:#6B7280;font-size:11px;">
                        ${jobTypes} - ${jobNumbers}
                        ${stop.driving_duration ? ` ⏱️ ${stop.driving_duration}` : ` ⏱️ ${timeText}`}
                    </div>
                    ${relatedJobs.length > 1 ? `<div style="color:#9CA3AF;font-size:10px;margin-top:2px;"> ${relatedJobs.length} jobs at this location</div>` : ''}
                </div>
                <div style="background:#F0F2F5;color:#6B7280;font-size:10px;padding:2px 10px;border-radius:12px;flex-shrink:0;white-space:nowrap;">${distanceText}</div>
            </div>
        `;
    });
    
    html += '</div>';
    
    stopsList.innerHTML = html;

    if (footerContainer) {
        footerContainer.innerHTML = `
            <div class="route-footer">
                <div class="footer-row">
                    <span class="footer-item"><strong>Algorithm:</strong> ${route.algorithm_used || 'Nearest Neighbor'}</span>
                    <span class="footer-item"><strong>Distance:</strong> Haversine Formula</span>
                    <span class="footer-item"><strong>Map:</strong> ${isOpenStreetMap ? 'OpenStreetMap' : 'Google Maps'}</span>
                    ${route.used_google_maps ? '<span class="footer-item"><strong> Google Maps API</strong> Active</span>' : ''}
                </div>
                <div class="footer-copyright">
                    Route Optimization v2.0 &copy; ${new Date().getFullYear()} | Sunquick Lanka Pvt Ltd
                </div>
            </div>
        `;
        footerContainer.style.display = 'block';
    }
}

function clearMarkers() {
    markers.forEach(marker => {
        if (isOpenStreetMap) {
            map.removeLayer(marker);
        } else {
            marker.setMap(null);
        }
    });
    markers = [];
    clearRoute();
}

function loadJobs() {
    $('#refreshBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');
    
    $.ajax({
        url: CONFIG.API_BASE_URL + '?action=jobs',
        method: 'GET',
        timeout: 30000,
        success: function(response) {
            console.log('Jobs response:', response);
            
            if (response.status === 'success' && response.jobs) {
                allJobsData = response.jobs;
                
                clearMarkers();
                addMarker(startPoint.lat, startPoint.lng, '🏢 Sunquick Lanka Pvt Ltd', true);
                
                response.jobs.forEach(job => {
                    addMarker(
                        parseFloat(job.geo_lat),
                        parseFloat(job.geo_lng),
                        `${job.store_name} - ${job.job_type}`
                    );
                });
                
                $('#totalJobs').text(response.total);
                document.querySelector('.job-count .count-number').textContent = response.total;
                
                if (response.unique_locations !== undefined) {
                    $('#uniqueLocations').text(response.unique_locations);
                    $('#duplicatesRemoved').text(response.duplicates);
                }
            } else {
                showNotification('No jobs found or invalid response', 'warning');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Failed to load jobs: ' + error, 'danger');
            console.error('Error loading jobs:', xhr.responseText);
        },
        complete: function() {
            $('#refreshBtn').prop('disabled', false).html(' Refresh Jobs');
        }
    });
}

function showNotification(message, type = 'info') {
    const colors = {
        info: '#2D9CDB',
        success: '#27AE60',
        warning: '#F2994A',
        danger: '#EB5757'
    };
    
    const notification = $(`
        <div style="position:fixed;bottom:20px;right:20px;background:${colors[type]};color:white;padding:12px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;max-width:400px;animation:slideUp 0.4s ease;">
            ${message}
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.fadeOut(400, function() {
            $(this).remove();
        });
    }, 5000);
}

function addAlgorithmSelector() {
    const container = $('#optimizeBtn').parent();
    
    const selector = `
        <div style="margin-bottom:12px;">
            <label style="color:#6B7280;font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Algorithm</label>
            <select id="algorithmSelect" style="width:100%;padding:8px 12px;border:1px solid #E4E7EC;border-radius:8px;background:white;font-size:13px;">
                <option value="nearest_neighbor">Nearest Neighbor</option>
                <option value="2-opt">2-Opt (Improved)</option>
            </select>
            <div style="color:#9CA3AF;font-size:10px;margin-top:4px;">2-Opt typically gives shorter routes</div>
        </div>
    `;
    
    container.before(selector);
}

$(document).ready(function() {
    console.log('Document ready, initializing...');
    
    initMap();
    
    loadJobs();
   
    addAlgorithmSelector();
  
    $('#optimizeBtn').click(function() {
        const algorithm = $('#algorithmSelect').val();
        const url = CONFIG.API_BASE_URL + '?action=optimize&algorithm=' + encodeURIComponent(algorithm);
        
        console.log('Optimizing with URL:', url);
        
        $('#optimizeBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Optimizing...');
        
        $.ajax({
            url: url,
            method: 'GET',
            timeout: 60000,
            success: function(response) {
                console.log('API Response:', response);
                
                if (response.status === 'success') {
                    console.log('Route data:', response.route);
                    routeData = response.route;
                    drawRoute(response.route);
                    showNotification('Route optimized successfully using ' + response.algorithm, 'success');
                } else {
                    showNotification('Failed to optimize route: ' + (response.error || 'Unknown error'), 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr, status, error);
                showNotification('Failed to optimize route: ' + error, 'danger');
            },
            complete: function() {
                $('#optimizeBtn').prop('disabled', false).html(' Optimize Route');
            }
        });
    });
    
    if (!isOpenStreetMap) {
        $('#optimizeWithMapsBtn').show();
        $('#optimizeWithMapsBtn').click(function() {
            $('#optimizeWithMapsBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Optimizing...');
            
            $.ajax({
                url: CONFIG.API_BASE_URL + '?action=optimize-with-maps',
                method: 'GET',
                timeout: 30000,
                success: function(response) {
                    if (response.status === 'success') {
                        drawRoute(response.route);
                        showNotification('Route optimized with Google Maps', 'success');
                    } else {
                        showNotification('Failed to optimize with Google Maps', 'danger');
                    }
                },
                error: function() {
                    showNotification('Failed to optimize with Google Maps', 'danger');
                },
                complete: function() {
                    $('#optimizeWithMapsBtn').prop('disabled', false).html(' Optimize with Google Maps');
                }
            });
        });
    }

    $('#refreshBtn').click(function() {
        loadJobs();
        $('#routeSummary').removeClass('visible');
        $('#routeSummary').addClass('d-none');
        $('#stopsList').html('');
        clearRoute();
        const footerContainer = document.getElementById('routeFooter');
        if (footerContainer) {
            footerContainer.style.display = 'none';
        }
        showNotification('Jobs refreshed', 'info');
    });
});
