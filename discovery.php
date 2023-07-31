<!DOCTYPE html>
<html>
<head>
<title>Publication MQTT Discovery pour la station météo</title>
</head>
<body>
<p>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once ('vendor/autoload.php');

use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\Exceptions\UnexpectedAcknowledgementException;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

// Configuration MQTT
const MQTT_HOST = '192.212.30.105';
const MQTT_PORT = 1883;
const MQTT_CLIENT_ID = 'mqtt_discovery_script';
const MQTT_USER = 'mqttuser';
const MQTT_PASSWORD = 'mqttuser';

// Connexion au broker MQTT
// Send it to MQTT
$mqtt = new MqttClient(MQTT_HOST, MQTT_PORT, MQTT_CLIENT_ID);
$settings = (new ConnectionSettings)
    ->setUsername(MQTT_USER)
    ->setPassword(MQTT_PASSWORD);
$mqtt->connect($settings, true);

// Fonction pour publier le JSON de découverte pour un capteur
function publishDiscoveryJSON($sensorName, $sensorType, $deviceClass, $unitOfMeasurement, $stateTopic, $icon) {
    global $mqttClientID;
    $deviceIdentifier = "pws_5_in_1_001"; // Remplacez par l'identifiant unique de votre station météo

    $json = array(
        "name" => "Station Meteo " . $sensorName,
        "uniq_id" => $deviceIdentifier . "_" . strtolower($sensorName),
        "state_topic" => $stateTopic,
        "unit_of_measurement" => $unitOfMeasurement,
        "icon" => $icon,
        "device" => array(
            "identifiers" => [$deviceIdentifier],
            "name" => "Station Meteo",
            "sw_version" => "1.0",
            "model" => "Bresser_5_in_1", // Remplacez par le modèle de votre station météo
            "manufacturer" => "Bresser" // Remplacez par le fabricant de votre station météo
        )
    );

    // Ajouter "device_class" au JSON uniquement si $deviceClass n'est pas vide
    if (!empty($deviceClass)) {
        $json["device_class"] = $deviceClass;
    }

    $topic = "homeassistant/sensor/" . $deviceIdentifier . "/" . strtolower($sensorName) . "/config";
    $payload = json_encode($json);
    
    global $mqtt;
    $mqtt->publish($topic, $payload, 1, true);
	sleep(2);
	// Affichage du JSON publié
    echo "JSON de découverte pour le capteur $sensorName publié :<br>";
    echo "<pre>" . htmlentities($payload) . "</pre>";
}

// Publier le JSON de découverte pour chaque capteur
publishDiscoveryJSON("Baromin", "sensor", "atmospheric_pressure", "hPa", "pws_5_in_1_001/sensors/baromin", "mdi:gauge");
publishDiscoveryJSON("OutdoorTemp", "sensor", "temperature", "°C", "pws_5_in_1_001/sensors/temp", "mdi:thermometer");
publishDiscoveryJSON("Dewpt", "sensor", "temperature", "°C", "pws_5_in_1_001/sensors/dewpt", "mdi:thermometer");
publishDiscoveryJSON("OutdoorHumidity", "sensor", "humidity", "%", "pws_5_in_1_001/sensors/humidity", "mdi:water-percent");
publishDiscoveryJSON("WindSpeedKph", "sensor", "wind_speed", "km/h", "pws_5_in_1_001/sensors/windspeedkph", "mdi:weather-windy");
publishDiscoveryJSON("WindGustKph", "sensor", "wind_speed", "km/h", "pws_5_in_1_001/sensors/windgustkph", "mdi:weather-windy");
publishDiscoveryJSON("WindDirection", "sensor", "", "°", "pws_5_in_1_001/sensors/winddirection", "mdi:compass");
publishDiscoveryJSON("RainMm", "sensor", "precipitation", "mm", "pws_5_in_1_001/sensors/rainmm", "mdi:weather-rainy");
publishDiscoveryJSON("DailyRainMm", "sensor", "precipitation", "mm", "pws_5_in_1_001/sensors/dailyrainmm", "mdi:weather-rainy");
publishDiscoveryJSON("IndoorTemp", "sensor", "temperature", "°C", "pws_5_in_1_001/sensors/indoortemp", "mdi:thermometer");
publishDiscoveryJSON("IndoorHumidity", "sensor", "humidity", "%", "pws_5_in_1_001/sensors/indoorhumidity", "mdi:water-percent");

// Déconnexion du broker MQTT
$mqtt->disconnect();
unset($mqtt);

?>
success
</p>
</body>
</html>
