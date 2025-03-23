<?php

header('Content-Type: application/json');

// Read waypoints from the request
$inputData = json_decode(file_get_contents("php://input"), true);
$routes = isset($inputData['waypoints']) ? $inputData['waypoints'] : [];

if (empty($routes)) {
    echo json_encode([]);
    exit;
}

// Geocycle Philippines HQ coordinates
$hqLat = 14.54391234;
$hqLon = 121.04958012;

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Radius of the earth in km

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c; // Distance in km
}

function findNearestWaypoint($routes, $hqLat, $hqLon) {
    $nearestIndex = -1;
    $nearestDistance = PHP_INT_MAX;

    foreach ($routes as $index => $waypoint) {
        $distance = calculateDistance($hqLat, $hqLon, $waypoint['latitude'], $waypoint['longitude']);
        if ($distance < $nearestDistance) {
            $nearestDistance = $distance;
            $nearestIndex = $index;
        }
    }

    return $nearestIndex;
}

function tsp($routes, $hqLat, $hqLon) {
    $n = count($routes);
    if ($n == 0) return [];

    // Find the nearest waypoint to HQ
    $startIndex = findNearestWaypoint($routes, $hqLat, $hqLon);

    // Reorder waypoints to start from nearest to HQ
    $visited = array_fill(0, $n, false);
    $routeOrder = [];
    $currentIndex = $startIndex;

    for ($i = 0; $i < $n; $i++) {
        $visited[$currentIndex] = true;
        $routeOrder[] = $routes[$currentIndex];
        $nearestIndex = -1;
        $nearestDistance = PHP_INT_MAX;

        for ($j = 0; $j < $n; $j++) {
            if (!$visited[$j]) {
                $distance = calculateDistance(
                    $routes[$currentIndex]['latitude'],
                    $routes[$currentIndex]['longitude'],
                    $routes[$j]['latitude'],
                    $routes[$j]['longitude']
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $j;
                }
            }
        }

        if ($nearestIndex != -1) {
            $currentIndex = $nearestIndex;
        }
    }

    return $routeOrder;
}

$optimizedRoutes = tsp($routes, $hqLat, $hqLon);
echo json_encode($optimizedRoutes);
exit;
