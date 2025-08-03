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

const MQTT_HOST = '192.212.10.105';
const MQTT_PORT = 1883;
const MQTT_CLIENT_ID = 'weather-data-publisher';
const MQTT_USER = 'mqttuser';
const MQTT_PASSWORD = 'mqttuser';
const TOPIC = 'pws_5_in_1_001/sensors/';

$myfile = fopen("last_SERVER_QUERY_STRING.txt", "w") or die("Unable to open file!");
fwrite($myfile, $_SERVER['QUERY_STRING']);
fclose($myfile);

$winddir = $_GET["winddir"];

$wspeed = $_GET["windspeedmph"];
$wgust = $_GET["windgustmph"];
$hum = $_GET["humidity"];
$dew = $_GET["dewptf"];
$temp = $_GET["tempf"];
$rainhour = $_GET["rainin"];
$rainday = $_GET["dailyrainin"];
$baro = $_GET["baromin"];

function RoundIt($ee){
  return round($ee, 2);
}
function toKM( $a) {
  return  RoundIt( floatval($a)*1.60934);
}
function toC( $a) {
  return RoundIt(  (floatval($a)-32) * (5/9) );
}
function toMM( $a) {
    return RoundIt( floatval($a)*25.4);
}
  
function toHPA( $a) {
  return RoundIt((floatval($a)*33.8639));
}

function wind_cardinal( $degree ) { 
  switch( $degree ) {
      case ( $degree >= 348.75 && $degree <= 360 ):
          $cardinal = "N";
      break;
      case ( $degree >= 0 && $degree <= 11.249 ):
          $cardinal = "N";
      break;
      case ( $degree >= 11.25 && $degree <= 33.749 ):
          $cardinal = "NNE";
      break;
      case ( $degree >= 33.75 && $degree <= 56.249 ):
          $cardinal = "NE";
      break;
      case ( $degree >= 56.25 && $degree <= 78.749 ):
          $cardinal = "ENE";
      break;
      case ( $degree >= 78.75 && $degree <= 101.249 ):
          $cardinal = "E";
      break;
      case ( $degree >= 101.25 && $degree <= 123.749 ):
          $cardinal = "ESE";
      break;
      case ( $degree >= 123.75 && $degree <= 146.249 ):
          $cardinal = "SE";
      break;
      case ( $degree >= 146.25 && $degree <= 168.749 ):
          $cardinal = "SSE";
      break;
      case ( $degree >= 168.75 && $degree <= 191.249 ):
          $cardinal = "S";
      break;
      case ( $degree >= 191.25 && $degree <= 213.749 ):
          $cardinal = "SSW";
      break;
      case ( $degree >= 213.75 && $degree <= 236.249 ):
          $cardinal = "SW";
      break;
      case ( $degree >= 236.25 && $degree <= 258.749 ):
          $cardinal = "WSW";
      break;
      case ( $degree >= 258.75 && $degree <= 281.249 ):
          $cardinal = "W";
      break;
      case ( $degree >= 281.25 && $degree <= 303.749 ):
          $cardinal = "WNW";
      break;
      case ( $degree >= 303.75 && $degree <= 326.249 ):
          $cardinal = "NW";
      break;
      case ( $degree >= 326.25 && $degree <= 348.749 ):
          $cardinal = "NNW";
      break;
      default:
          $cardinal = null;
  }
 return $cardinal;
}

// Send it to MQTT
$mqtt = new MqttClient(MQTT_HOST, MQTT_PORT, MQTT_CLIENT_ID);

$settings = (new ConnectionSettings)
    ->setUsername(MQTT_USER)
    ->setPassword(MQTT_PASSWORD);

$mqtt->connect($settings, true);

$mqtt->publish(TOPIC.'baromin', toHPA($_GET["baromin"]), 0);
$mqtt->publish(TOPIC .'temp', toC($_GET["tempf"]), 0);
$mqtt->publish(TOPIC .'dewpt', toC($_GET["dewptf"]), 0);
$mqtt->publish(TOPIC .'humidity', $_GET["humidity"], 0);
$mqtt->publish(TOPIC .'windspeedkph', toKM($_GET["windspeedmph"]), 0);
$mqtt->publish(TOPIC .'windgustkph', toKM($_GET["windgustmph"]), 0);

// Use this WindDir if you want wind direction in degrees
$mqtt->publish(TOPIC .'winddirection', $_GET["winddir"], 0);

$mqtt->publish(TOPIC .'rainmm', toMM($_GET["rainin"]), 0);
$mqtt->publish(TOPIC .'dailyrainmm', toMM($_GET["dailyrainin"]), 0);
$mqtt->publish(TOPIC .'indoortemp', toC($_GET["indoortempf"]), 0);
$mqtt->publish(TOPIC .'indoorhumidity', $_GET["indoorhumidity"], 0);

$mqtt->disconnect();

// POST TO WU
$xml = file_get_contents("http://pws-ingest-use1-01.sun.weather.com/weatherstation/updateweatherstation.php?".$_SERVER['QUERY_STRING']);

?>
success
