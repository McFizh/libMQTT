<?PHP

class ClientTest2 extends PHPUnit_Framework_TestCase {

    private $message1Received = false,
            $message2Received = false,
            $message3Received = false,
            $message4Received = false,
            $message5Received = false;

    public function testUnencryptedAuthorizedLengthyClientCreation()
    {
        // Try to establish connection to server
        $client = new LibMQTT\Client("localhost",1883,"phpUnitClientWithMaxLen");
        $client->setAuthDetails("somereallyweirdandlongusernametotestoutconnectpacetsizeproblem", "withsomeaccompanyinglongcatpasswordthatnoonewoulduseasitmighteventincludetypos");

        $result = $client->connect();
        $this->assertTrue($result);

        //
        $client->close();
    }

    public function testUnencryptedAuthorizedClientCreation()
    {

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

        // Publish loooong message (2 bytes) with QoS 0 to channel
        $result = $client->publish("libmqtt/wcard/channel", "test message 4 : xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", 0);
        $this->assertTrue($result);

        // Publish even loooonger message (3 bytes) with QoS 1 to channel
        $msg = "test message 5 : ";
        for($l1=0; $l1<25000; $l1++)
            $msg.="x";

        $result = $client->publish("libmqtt/test", $msg, 1);
        $this->assertTrue($result);

        // Wait 7 x 40ms for server to send us the previous messages
        for($l1=0; $l1<7; $l1++) {
            usleep(40000);
            $client->eventloop();
        }

        // By now, we should have received all messages back to us
        // and also the test message 2 should have been acknowledged
        $this->assertTrue( count( $client->getMessageQueue() ) == 0 );
        $this->assertTrue($this->message1Received, "Message 1 not received");
        $this->assertTrue($this->message2Received, "Message 2 not received");
        $this->assertTrue($this->message3Received, "Message 3 not received");
        $this->assertTrue($this->message4Received, "Message 4 not received");
        $this->assertTrue($this->message5Received, "Message 5 not received");

        //
        $client->close();

    }

    public function handleReceivedMessages($topic, $msg, $qos) 
    {
        if( !in_array($topic, array("libmqtt/test","libmqtt/wcard/test","libmqtt/wcard/channel") ) )
            return;

        if($msg == "test message 1" && $qos==0)
            $this->message1Received = true;
        if($msg == "test message 2" && $qos==1)
            $this->message2Received = true;
        if($msg == "test message 3" && $qos==0)
            $this->message3Received = true;
        if(substr($msg,0,14) == "test message 4" && strlen($msg)>200 && $qos==0)
            $this->message4Received = true;
        if(substr($msg,0,14) == "test message 5" && strlen($msg)>25000 && $qos==1)
            $this->message5Received = true;
    }

}
