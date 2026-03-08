<?php
include 'config.php';

header('Content-Type: application/json');

function get_ip_address()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

$ip = get_ip_address();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referrer = $_POST['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$path = $_POST['path'] ?? ($_SERVER['REQUEST_URI'] ?? '');

$country = '';
$region = '';
$city = '';

if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
    $url = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=status,country,regionName,city';
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (is_array($data) && ($data['status'] ?? '') === 'success') {
            $country = $data['country'] ?? '';
            $region = $data['regionName'] ?? '';
            $city = $data['city'] ?? '';
        }
    }
}

$stmt = $conn->prepare("INSERT INTO visitor_logs (ip_address, user_agent, referrer, path, country, region, city) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $ip, $userAgent, $referrer, $path, $country, $region, $city);
$stmt->execute();
$stmt->close();

echo json_encode(['status' => 'ok']);
