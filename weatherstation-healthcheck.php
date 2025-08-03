<?php
date_default_timezone_set('UTC');

// Config
$filename = __DIR__ . '/last_SERVER_QUERY_STRING.txt';
$max_age_minutes = 10;

// Read the JSON file
if (!file_exists($filename)) {
    http_response_code(500);
    echo "File not found: last_SERVER_QUERY_STRING.txt";
    exit;
}

$content = file_get_contents($filename);
$data = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['timestamp'])) {
    http_response_code(500);
    echo "Invalid JSON or missing timestamp.";
    exit;
}

// Parse the timestamp
$last_update_time = strtotime($data['timestamp']);
if ($last_update_time === false) {
    http_response_code(500);
    echo "Invalid timestamp format: " . $data['timestamp'];
    exit;
}

// Check freshness
$now = time();
$age_seconds = $now - $last_update_time;

if ($age_seconds <= ($max_age_minutes * 60)) {
    http_response_code(200);
    echo "Weather station updated recently: " . $data['timestamp'];
} else {
    http_response_code(500);
    echo "Stale update. Last timestamp: " . $data['timestamp'];
}
