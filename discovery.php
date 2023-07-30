<?php

// Configuration MQTT
$mqttBroker = "tcp://your_mqtt_broker_ip:1883";
$mqttClientID = "mqtt_discovery_script";
$mqttUsername = "your_mqtt_username";
$mqttPassword = "your_mqtt_password";

// Connexion au broker MQTT
$mqtt = new Mosquitto\Client($mqttClientID);
$mqtt->setCredentials($mqttUsername, $mqttPassword);
$mqtt->connect($mqttBroker);
$mqtt->loop();

// Fonction pour publier le JSON de découverte pour un capteur
function publishDiscoveryJSON($sensorName, $sensorType, $deviceClass, $unitOfMeasurement, $stateTopic, $icon) {
    global $mqttClientID;
    $deviceIdentifier = "pws_5_in_1_001"; // Remplacez par l'identifiant unique de votre station météo

    $json = array(
        "name" => "Station Meteo - " . $sensorName,
        "uniq_id" => $deviceIdentifier . "_" . strtolower($sensorName),
        "device_class" => $deviceClass,
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

    $topic = "homeassistant/sensor/" . $deviceIdentifier . "/" . strtolower($sensorName) . "/config";
    $payload = json_encode($json);
    
    global $mqtt;
    $mqtt->publish($topic, $payload, 1, true);
    $mqtt->loop();
}

// Publier le JSON de découverte pour chaque capteur
publishDiscoveryJSON("Baromin", "sensor", "pressure", "hPa", "pws/sensor/baromin", "mdi:gauge");
publishDiscoveryJSON("Temp", "sensor", "temperature", "°C", "pws/sensor/temp", "mdi:thermometer");
publishDiscoveryJSON("Dewpt", "sensor", "temperature", "°C", "pws/sensor/dewpt", "mdi:thermometer");
publishDiscoveryJSON("Humidity", "sensor", "humidity", "%", "pws/sensor/humidity", "mdi:water-percent");
publishDiscoveryJSON("Windspeedkph", "sensor", "speed", "km/h", "pws/sensor/windspeedkph", "mdi:weather-windy");
publishDiscoveryJSON("Windgustkph", "sensor", "speed", "km/h", "pws/sensor/windgustkph", "mdi:weather-windy");
publishDiscoveryJSON("Winddirection", "sensor", "direction", "°", "pws/sensor/winddirection", "mdi:compass");
publishDiscoveryJSON("Rainmm", "sensor", "rain", "mm", "pws/sensor/rainmm", "mdi:weather-rainy");
publishDiscoveryJSON("Dailyrainmm", "sensor", "rain", "mm", "pws/sensor/dailyrainmm", "mdi:weather-rainy");
publishDiscoveryJSON("Indoortemp", "sensor", "temperature", "°C", "pws/sensor/indoortemp", "mdi:thermometer");
publishDiscoveryJSON("Indoorhumidity", "sensor", "humidity", "%", "pws/sensor/indoorhumidity", "mdi:water-percent");

// Déconnexion du broker MQTT
$mqtt->disconnect();
unset($mqtt);
