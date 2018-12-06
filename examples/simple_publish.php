<?PHP

// File created by: composer dump-autoload -o
require "../vendor/autoload.php";

// Create new MQTT client
$client = new LibMQTT\Client("serverName",1883,"ThisIsYourUniqueClientID");

// Should you need authentication, you can set them here
#$client->setAuthDetails("username", "userpass");

// Try to establish connection to server
$result = $client->connect();

// Publish message to "topic1/topic2" with qos 0
$client->publish("topic1/topic2", "Test Message", 0);

// Close the connection.
$client->close();
