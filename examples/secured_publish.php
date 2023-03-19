<?PHP
use McFish\LibMQTT\Client;

// Try to establish connection to server
$client = new Client("serverName",8883,"ThisIsYourUniqueClientID");

// Set connection protocol ("tls" = all tls versions)
$client->setCryptoProtocol("tls");

// If server is running with self-signed certificate, provide CA file here
#$client->setCAFile("path_to_cafile");

$result = $client->connect();

// Publish message to "topic1/topic2"
$client->publish("topic1/topic2", "Test Message", 0);

// Close the connection.
$client->close();
