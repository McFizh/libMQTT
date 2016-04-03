<?PHP

class ClientTest2 extends PHPUnit_Framework_TestCase {

	public function testUnencryptedAuthorizedClientCreation() {

		// Try to establish connection to server
		$client = new LibMQTT\Client("localhost",1883,"phpUnitClient");
		$client->setAuthDetails("testuser", "userpass");
		
		//
		$result = $client->connect();
		$this->assertTrue($result);

		$client->publish("libmqtt/test", "testi", 0);

		//
		$client->close();

	}

}
