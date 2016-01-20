<?PHP

class ClientTest extends PHPUnit_Framework_TestCase {

	public function testUnencryptedClientCreation() {

		// Try to establish connection to server
		$client = new LibMQTT\Client("test.mosquitto.org",1883,"phpUnitClient");
		
		//
		$result = $client->connect();
		$this->assertTrue($result);

		$client->publish("libmqtt/test", "testi", 0);

		//
		$client->close();

	}

	public function testCryptedClientCreation() {

		// Try to establish connection to server
		$client = new LibMQTT\Client("test.mosquitto.org",8883,"phpUnitClient");
		$client->setCryptoProtocol("tls");
		$client->setCAFile("tests/mosquitto.org.crt");

		//
		$result = $client->connect();
		$this->assertTrue($result);

		$client->publish("libmqtt/test", "testi", 0);

		//
		$client->close();

	}

}
