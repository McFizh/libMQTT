<?PHP

class ClientTest2 extends PHPUnit_Framework_TestCase {

	private $message1Received = false,
		$message2Received = false,
		$message3Received = false;

	public function testUnencryptedAuthorizedClientCreation() {

		// Try to establish connection to server
		$client = new LibMQTT\Client("localhost",1883,"phpUnitClient");
		$client->setAuthDetails("testuser", "userpass");
		
		$result = $client->connect();
		$this->assertTrue($result);

		// Subscribe to 'libmqtt/test' and 'libmqtt/empty' channels
		$result = $client->subscribe( [ 
			"libmqtt/test" => [ "qos" => 1 , "function" => [ $this, "handleReceivedMessages" ] ],
			"libmqtt/empty" => [ "qos" => 0 , "function" => [ $this, "handleReceivedMessages" ] ],
			"libmqtt/wcard/+" => [ "qos" => 0 , "function" => [ $this, "handleReceivedMessages" ] ]
		] );
		$this->assertTrue($result);

		// Publish test message with QoS 0 to channel
		$result = $client->publish("libmqtt/test", "test message 1", 0);
		$this->assertTrue($result);

		// Publish test message with QoS 1 to channel
		$result = $client->publish("libmqtt/test", "test message 2", 1);
		$this->assertTrue($result);
		$this->assertTrue( count( $client->getMessageQueue() ) == 1 );

		// Publish test message with QoS 0 to channel
		$result = $client->publish("libmqtt/wcard/test", "test message 3", 0);
		$this->assertTrue($result);

		// Wait 5 x 20ms for server to send us the previous messages
		for($l1=0; $l1<5; $l1++)
		{
			usleep(20000);
			$client->eventloop();
		}

		// By now, we should have received all messages back to us
		// and also the test message 2 should have been acknowledged
		$this->assertTrue( count( $client->getMessageQueue() ) == 0 );
		$this->assertTrue($this->message1Received);
		$this->assertTrue($this->message2Received);
		$this->assertTrue($this->message3Received);

		//
		$client->close();

	}

	public function handleReceivedMessages($topic, $msg, $qos) {
		if($topic != "libmqtt/test")
			return;
		if($msg == "test message 1" && $qos==0)
			$this->message1Received = true;
		if($msg == "test message 2" && $qos==1)
			$this->message2Received = true;
		if($msg == "test message 3" && $qos==0)
			$this->message3Received = true;
	}

}
