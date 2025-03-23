<!-- filepath: c:\xamppSAD\htdocs\SADsystem\collector_dashboard.php -->
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['collector_id'])) {
    header("Location: ../login.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <h2>🚛 Collector Dashboard</h2>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>📋 Pickup History</h5>
                    <p>View all completed pickups.</p>
                    <a href="pickup_history.php" class="btn btn-success">View History</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>🔄 Reschedule Requests</h5>
                    <p>Check the status of rescheduled pickups.</p>
                    <a href="reschedule_pickups.php" class="btn btn-warning">View Requests</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5>📦 Assigned Pickups</h5>
                    <div id="map" style="height: 500px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Leaflet.js CSS and JavaScript -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<!-- Include Leaflet Routing Machine CSS and JavaScript -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    function initMap() {
    var map = L.map('map').setView([14.5534, 121.0490], 15); // Center in BGC

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Restrict movement to BGC
    var southWest = L.latLng(14.5498, 121.0445),
        northEast = L.latLng(14.5570, 121.0535);
    var bounds = L.latLngBounds(southWest, northEast);
    map.setMaxBounds(bounds);
    map.on('drag', function () {
        map.panInsideBounds(bounds, { animate: false });
    });

    fetch('collector/fetch_pickups.php')
        .then(response => response.json())
        .then(data => {
            console.log("Fetched Data:", data);

            if (!Array.isArray(data) || data.length === 0) {
                console.warn("No assigned pickups found.");
                return;
            }

            var markers = [];
            var waypoints = [];
            var bounds = L.latLngBounds();

            // Convert data into an array of objects with coordinates
            var locations = data.map(pickup => ({
                lat: parseFloat(pickup.latitude),
                lon: parseFloat(pickup.longitude),
                building: pickup.building,
                request_id: pickup.request_id
            }));

            if (locations.length === 0) {
                console.warn("No valid locations found.");
                return;
            }

            // Optimize route using a simple Nearest Neighbor approach
            function optimizeRoute(locations) {
                let optimized = [];
                let remaining = [...locations];

                // Start from the first location
                optimized.push(remaining.shift());

                while (remaining.length > 0) {
                    let last = optimized[optimized.length - 1];
                    let nearestIndex = 0;
                    let minDist = Infinity;

                    remaining.forEach((loc, index) => {
                        let dist = Math.sqrt(Math.pow(last.lat - loc.lat, 2) + Math.pow(last.lon - loc.lon, 2));
                        if (dist < minDist) {
                            minDist = dist;
                            nearestIndex = index;
                        }
                    });

                    optimized.push(remaining.splice(nearestIndex, 1)[0]);
                }

                return optimized;
            }

            // Sort waypoints for better routing
            let optimizedLocations = optimizeRoute(locations);

            optimizedLocations.forEach(loc => {
                console.log(`Adding marker at: ${loc.lat}, ${loc.lon}`);

                var marker = L.marker([loc.lat, loc.lon]).addTo(map)
                    .bindPopup(`<b>${loc.building}</b><br>Request ID: ${loc.request_id}`);

                markers.push(marker);
                waypoints.push(L.latLng(loc.lat, loc.lon));
                bounds.extend(marker.getLatLng());
            });

            if (markers.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30] });
            }

            // ✅ Optimized Routing with Minimize Feature
            if (waypoints.length > 1) {
                var routingControl = L.Routing.control({
                    waypoints: waypoints,
                    routeWhileDragging: false,
                    createMarker: () => null, // Prevents duplicate markers
                    lineOptions: { styles: [{ color: 'blue', weight: 4 }] } // Blue route line
                }).addTo(map);

                // Initially hide the directions panel
                var routingContainer = document.querySelector('.leaflet-routing-container');
                if (routingContainer) {
                    routingContainer.style.display = 'none';
                }

                // Add a toggle button for show/hide
                var toggleButton = L.control({ position: 'topright' });
                toggleButton.onAdd = function (map) {
                    var div = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                    div.innerHTML = `<button id="toggleRouting" style="padding:5px; background:#fff; border-radius:5px; cursor:pointer;">📍 Show Directions</button>`;
                    return div;
                };
                toggleButton.addTo(map);

                // Handle button click to toggle the panel
                document.getElementById('toggleRouting').addEventListener('click', function () {
                    var routingPanel = document.querySelector('.leaflet-routing-container');

                    if (!routingPanel) {
                        console.error("Routing panel not found.");
                        return;
                    }

                    if (routingPanel.style.display === 'none' || routingPanel.style.display === '') {
                        routingPanel.style.display = 'block';
                        this.innerHTML = ' Hide Directions';
                    } else {
                        routingPanel.style.display = 'none';
                        this.innerHTML = ' Show Directions';
                    }
                });
            }
        })
        .catch(error => console.error("Error fetching data:", error));
}

window.onload = initMap;

</script>

<?php include 'includes/footer.php'; ?>