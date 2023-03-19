<?PHP
use PHPUnit\Framework\TestCase;
use McFish\LibMQTT\Client;

class Client1 extends TestCase {

	public function testUnencryptedClientCreation() {

		// Try to establish connection to server
		$client = new Client("localhost",1883,"phpUnitClient");
		$client->setAuthDetails("testuser", "userpass");

		//
		$result = $client->connect();
		$this->assertTrue($result);

		// Subscribe to 'libmqtt/test' and 'libmqtt/empty' channels
		$result = $client->subscribe( [
			"libmqtt/test" => [ "qos" => 1 ],
			"libmqtt/empty" => [ "qos" => 0 ]
		] );
		$this->assertTrue($result);

		//
		$result = $client->publish("libmqtt/test", "testi", 0);
		$this->assertTrue($result);

		//
		$client->close();
	}

	public function testCryptedClientCreation() {

		// Try to establish connection to server
		$client = new Client("test.mosquitto.org",8883,"phpUnitClient");
		$client->setCryptoProtocol("tls");
		$client->setCAFile("tests/mosquitto.org.crt");

		//
		$result = $client->connect();
		$this->assertTrue($result);

		// Subscribe to 'libmqtt/test' and 'libmqtt/empty' channels
		$result = $client->subscribe( [
			"libmqtt/test" => [ "qos" => 1 ],
			"libmqtt/empty" => [ "qos" => 0 ]
		] );
		$this->assertTrue($result);

		//
		$result = $client->publish("libmqtt/test", "testi", 0);
 		$this->assertTrue($result);

		//
		$client->close();

	}
}
