<?php
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    echo json_encode(["error" => "Latitude and longitude required"]);
    exit;
}

$latitude = $_GET['lat'];
$longitude = $_GET['lon'];
$HERE_API_KEY = "C6KujiAPAJN4e40rJg1NSTliE0TlrtrYkERFXGmbcRQ"; // Replace with your HERE API Key

$here_url = "https://revgeocode.search.hereapi.com/v1/revgeocode?at=$latitude,$longitude&apikey=$HERE_API_KEY";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $here_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$address = "Address not found";
if ($response) {
    $data = json_decode($response, true);
    if (!empty($data['items'])) {
        $address = $data['items'][0]['address']['label'];
    }
}

// Define office location
$office_lat = 22.984277; // Example latitude (San Francisco)
$office_lon = 72.614815; // Example longitude (San Francisco)

// Calculate distance
function haversine($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

$distance = haversine($latitude, $longitude, $office_lat, $office_lon);
$inside_office = $distance <= 0.5; // 100 meters radius

$message = $inside_office ? "✅ Inside Office Area" : "❌ Outside Office Area";

echo json_encode(["message" => $message, "address" => $address]);
?>
