<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('vendor/autoload.php');

use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\Exceptions\UnexpectedAcknowledgementException;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

const MQTT_HOST = '192.212.10.105';
const MQTT_PORT = 1883;
const MQTT_CLIENT_ID = 'weather-data-publisher';
const MQTT_USER = 'mqttuser';
const MQTT_PASSWORD = 'mqttuser';
const TOPIC = 'pws_5_in_1_001/sensors/';
const LAST_QUERY_FILE = 'last_SERVER_QUERY_STRING.txt';
const WU_RESPONSE_FILE = 'wu_response.txt';



// Show interface if no parameters
if (empty($_GET)) {
    echo "<h2>Status: Last Weather Station Call</h2>";

    if (file_exists(LAST_QUERY_FILE)) {
        $jsonData = file_get_contents(LAST_QUERY_FILE);
        $data = json_decode($jsonData, true);

        if ($data) {
            echo "<p><strong>Last update:</strong> " . ($data['timestamp'] ?? 'Unknown') . "</p>";
            echo "<p><strong>Source IP:</strong> " . ($data['ip'] ?? 'Unknown') . "</p>";
            
            if (!empty($data['query_string'])) {
                parse_str($data['query_string'], $parsed);
                echo "<h3>Last Query Parameters:</h3>";
                echo "<pre>";
                print_r($parsed);
                echo "</pre>";
            }
        } else {
            echo "Could not parse last call data.";
        }
    } else {
        echo "<p>No previous query recorded.</p>";
    }

    if (file_exists(WU_RESPONSE_FILE)) {
        $wuTimestamp = filemtime(WU_RESPONSE_FILE);
        $wuFormattedTime = date("Y-m-d H:i:s", $wuTimestamp);

        $wuContent = file_get_contents(WU_RESPONSE_FILE);

        echo "<p><strong>WU Response saved at:</strong> $wuFormattedTime</p>";
        echo "<h3>WU Response Content:</h3>";
        echo "<pre>" . htmlspecialchars($wuContent) . "</pre>";
    } else {
        echo "<p>No WU response recorded yet.</p>";
    }
    exit;
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // In case of multiple IPs, take the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$clientIp = getClientIp();

// Save incoming query string
$data = [
    'ip' => $clientIp,
    'query_string' => $_SERVER['QUERY_STRING'],
    'timestamp' => date("Y-m-d H:i:s"),
];
file_put_contents(LAST_QUERY_FILE, json_encode($data));

function RoundIt($ee) {
    return round($ee, 2);
}
function toKM($a) {
    return RoundIt(floatval($a) * 1.60934);
}
function toC($a) {
    return RoundIt((floatval($a) - 32) * (5 / 9));
}
function toMM($a) {
    return RoundIt(floatval($a) * 25.4);
}
function toHPA($a) {
    return RoundIt(floatval($a) * 33.8639);
}
function wind_cardinal($degree) {
    switch (true) {
        case ($degree >= 348.75 || $degree <= 11.249): return "N";
        case ($degree >= 11.25 && $degree <= 33.749): return "NNE";
        case ($degree >= 33.75 && $degree <= 56.249): return "NE";
        case ($degree >= 56.25 && $degree <= 78.749): return "ENE";
        case ($degree >= 78.75 && $degree <= 101.249): return "E";
        case ($degree >= 101.25 && $degree <= 123.749): return "ESE";
        case ($degree >= 123.75 && $degree <= 146.249): return "SE";
        case ($degree >= 146.25 && $degree <= 168.749): return "SSE";
        case ($degree >= 168.75 && $degree <= 191.249): return "S";
        case ($degree >= 191.25 && $degree <= 213.749): return "SSW";
        case ($degree >= 213.75 && $degree <= 236.249): return "SW";
        case ($degree >= 236.25 && $degree <= 258.749): return "WSW";
        case ($degree >= 258.75 && $degree <= 281.249): return "W";
        case ($degree >= 281.25 && $degree <= 303.749): return "WNW";
        case ($degree >= 303.75 && $degree <= 326.249): return "NW";
        case ($degree >= 326.25 && $degree < 348.75): return "NNW";
        default: return null;
    }
}

// Send to MQTT
try {
    $mqtt = new MqttClient(MQTT_HOST, MQTT_PORT, MQTT_CLIENT_ID);
    $settings = (new ConnectionSettings)
        ->setUsername(MQTT_USER)
        ->setPassword(MQTT_PASSWORD);

    $mqtt->connect($settings, true);

    $mqtt->publish(TOPIC . 'baromin', toHPA($_GET["baromin"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'temp', toC($_GET["tempf"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'dewpt', toC($_GET["dewptf"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'humidity', $_GET["humidity"] ?? 0, 0);
    $mqtt->publish(TOPIC . 'windspeedkph', toKM($_GET["windspeedmph"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'windgustkph', toKM($_GET["windgustmph"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'winddirection', $_GET["winddir"] ?? 0, 0);
    $mqtt->publish(TOPIC . 'rainmm', toMM($_GET["rainin"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'dailyrainmm', toMM($_GET["dailyrainin"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'indoortemp', toC($_GET["indoortempf"] ?? 0), 0);
    $mqtt->publish(TOPIC . 'indoorhumidity', $_GET["indoorhumidity"] ?? 0, 0);

    $mqtt->disconnect();
} catch (Exception $e) {
    error_log("MQTT Error: " . $e->getMessage());
    echo "MQTT Connection Error: " . $e->getMessage();
    exit;
}

// Send to Weather Underground
$wuResponse = file_get_contents("http://pws-ingest-use1-01.sun.weather.com/weatherstation/updateweatherstation.php?" . $_SERVER['QUERY_STRING']);

// Save WU response to file
file_put_contents(WU_RESPONSE_FILE, $wuResponse);

echo "success";
