<?php

use LibMQTT\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require('../../vendor/autoload.php');

$logger = new Logger('readBroker');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

try {
    $client = new Client('192.168.33.11', 1883, 'readClientID', $logger);
    $client->connect();
    $client->subscribe([
        'temperatures/#' =>  ['qos' => 1, 'function' => 'handleReceivedMessages'],
    ]);
} catch (\Exception $e) {
    printf('Captured exception: '.$e->getMessage().PHP_EOL);
    printf('Dying because nothing else can be done (within this example)');
    die();
}

$read = true;
while ($read === true) {
    $client->eventLoop();
    usleep(100000);
}

// Close the connection.
$client->close();

function handleReceivedMessages($msgTopic, $message, $QoS)
{
    printf('Received message on topic "%s": %s'.PHP_EOL, $msgTopic, $message);
    return true;
}
