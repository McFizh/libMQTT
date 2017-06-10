<?PHP

// File created by: composer dump-autoload -o
require '../vendor/autoload.php';

// Try to establish connection to server
$client = new LibMQTT\Client('serverName', 1883, 'ThisIsYourUniqueClientID');
$result = $client->connect();

// Publish message to "topic1/topic2"
$client->publish('topic1/topic2', 'Test Message', 0);

// Close the connection.
$client->close();
