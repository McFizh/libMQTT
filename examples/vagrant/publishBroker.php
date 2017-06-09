<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require('../../vendor/autoload.php');

$logger = new Logger('readBroker');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

try {
    $client = new LibMQTT\Client('192.168.33.11', 1883, 'publishClientID', $logger);
    $result = $client->connect();
} catch (\Exception $e) {
    printf('Captured exception: '.$e->getMessage().PHP_EOL);
    printf('Dying because nothing else can be done (within this example)');
    die();
}

$sensors = ['kitchen', 'workshop', 'bedroom/master', 'bedroom/kids', 'bathroom'];

// Publish message to "topic1/topic2"
$currentTemperature = mt_rand(16, 34);
$sendSensor = $sensors[mt_rand(0, count($sensors) - 1)];
$logger->info('Sending message', ['topic' => 'temperatures/'.$sendSensor, 'temp' => $currentTemperature]);
$client->publish('temperatures/'.$sendSensor, sprintf('%d°C', $currentTemperature), 0);

// Close the connection.
$client->close();
