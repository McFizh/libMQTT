<?PHP
/**
 * MQTT 3.1.1 library for PHP with TLS support
 *
 * @author Pekka Harjamäki <mcfizh@gmail.com>
 * @license MIT
 * @version 1.0.2
 */

namespace LibMQTT;

/**
 * Client class for MQTT
 */
class Client {

    /** @var int $timeSincePingReq      When the last PINGREQ was sent */
    public  $timeSincePingReq;

    /** @var int $timeSincePingResp     When the last PINGRESP was received */
    public  $timeSincePingResp;

    /** @var boolean $debug         Debug messages enabled? */
    private $debug;

    /** @var int $packet            ID of the next free packet */
    private $packet;

    /** @var array $topics          Array of topics we're subscribed to */
    private $topics;

    /** @var string $connMethod     Method used for connection */
    private $connMethod;

    /** @var resource $socket       Socket .. well.. socket */
    private $socket;

    /** @var string $serverAddress      Hostname of the server */
    private $serverAddress;

    /** @var string $serverPort     Port on the server */
    private $serverPort;

    /** @var string $clientID       ClientID for connection */
    private $clientID;

    /** @var string $caFile         CA file for server authentication */
    private $caFile;

    /** @var bool $verifyPeerName       Verify Certificate peer name */
    private $verifyPeerName;

    /** @var string $clientCrt      Certificate file for client authentication */
    private $clientCrt;

    /** @var string $clientKey      Key file for client authentication */
    private $clientKey;

    /** @var string $authUser       Username for authentication */
    private $authUser;

    /** @var string $authPass       Password for authentication */
    private $authPass;

    /** @var int $keepAlive         Link keepalive time */
    private $keepAlive;

    /** @var array $messageQueue    Messages published with QoS 1 are placed here, until they are confirmed */
    private $messageQueue;

    /**
     * Class constructor
     *
     * @param string $address Address of the broker
     * @param string $port Port on the broker
     * @param string $clientID clientID for the broker
     */
    function __construct($address, $port, $clientID)
    {
        $this->connMethod = "tcp";
        $this->socket = null;
        $this->serverAddress = null;
        $this->serverPort = null;
        $this->clientID = null;
        $this->authUser = null;
        $this->authPass = null;
        $this->keepAlive = 15;
        $this->packet = 1;
        $this->topics = [];
        $this->messageQueue = [];
        $this->verifyPeerName = true;

        // Basic validation of clientid
        if( preg_match("/[^0-9a-zA-Z]/",$clientID) ) {
            error_log("ClientId can only contain characters 0-9,a-z,A-Z");
            return;
        }

        if( strlen($clientID) > 23 ) {
            error_log("ClientId max length is 23 characters/numbers");
            return;
        }

        //
        $this->serverAddress = $address;
        $this->serverPort = $port;
        $this->clientID = $clientID;
    }

    /**
     * Class destructor
     */
    function __destruct()
    {
        if($this->socket)
            $this->close();
    }

    /**
     * Try to connect to broker
     *
     * @param boolean $clean Is this connection clean?
     *
     * @return boolean Returns false if connection failed
     */
    function connect($clean = true)
    {

        // Don't do anything, if server address is not set
        if(!$this->serverAddress) {
            return false;
        }

        // Is encryption enabled?
        if($this->connMethod!="tcp") {
            $socketContextOptions = [ "ssl" => [] ];
            $socketContextOptions["ssl"]["verify_peer_name"] = $this->verifyPeerName;

            if($this->caFile) {
                $socketContextOptions["ssl"]["cafile"]=$this->caFile;
            }

            if($this->clientCrt) {
                $socketContextOptions["ssl"]["local_cert"]=$this->clientCrt;
            }

            if ($this->clientCrt && $this->clientKey) {
                $socketContextOptions["ssl"]["local_pk"]=$this->clientKey;
            }

            $socketContext = stream_context_create($socketContextOptions);
            $host = $this->connMethod . "://" . $this->serverAddress . ":" . $this->serverPort;

            $this->socket = stream_socket_client(
                $host, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $socketContext);

            $this->debugMessage("Connecting to: ".$host);
        } else {
            $host = $this->connMethod . "://" . $this->serverAddress . ":" . $this->serverPort;
            $this->socket = stream_socket_client(
                $host, $errno, $errstr, 60, STREAM_CLIENT_CONNECT );
            $this->debugMessage("Connecting to: ".$host);
        }

        //
        if(!$this->socket) {
            $this->socket = null;
            $this->debugMessage("Connection failed.. $errno , $errstr");
            return false;
        }

        //
        stream_set_timeout($this->socket, 10);
        stream_set_blocking($this->socket, false);

        $bytes = 0;
        $buffer = "";

        // ------------------------------------
        // Calculate connect flags

        $var = $clean?2:0;
/*
        if($this->will != NULL)
        {
            $var += 4;                              // Set will flag
            $var += ($this->will['qos'] << 3);      // Set will qos

            if($this->will['retain'])
                $var += 32;                     // Set will retain
        }
*/

        if($this->authPass != NULL) {
            $var += 64;
        }

        if($this->authUser != NULL) {
            $var += 128;
        }

        // ------------------------------------
        // Create CONNECT packet (for MQTT 3.1.1 protocol)

        $buffer .= $this->convertString("MQTT", $bytes);

        $buffer .= chr(0x04);   // Protocol level
        $bytes++;

        $buffer .= chr($var);   // Connect flags
        $bytes++;

        $buffer .= chr($this->keepAlive >> 8);  // Keepalive (MSB)
        $bytes++;

        $buffer .= chr($this->keepAlive & 0xff);    // Keepalive (LSB)
        $bytes++;

        $buffer .= $this->convertString($this->clientID,$bytes);

        //Adding will to payload
        /*
        if($this->will != NULL){
            $buffer .= $this->strwritestring($this->will['topic'],$bytes);
            $buffer .= $this->strwritestring($this->will['content'],$bytes);
        }
        */

        if($this->authUser) {
            $buffer .= $this->convertString($this->authUser,$bytes);
        }

        if($this->authPass) {
            $buffer .= $this->convertString($this->authPass,$bytes);
        }

        $header = $this->createHeader( 0x10 , $bytes );
        fwrite($this->socket, $header, strlen($header));
        fwrite($this->socket, $buffer);

        // Wait for CONNACK packet
        $string = $this->readBytes(4, false);
        if(strlen($string)!=4) {
            $this->debugMessage("Connection failed! Server gave unexpected response (".strlen($string)." bytes).");
            return false;
        }

        if(ord($string{0}) == 0x20 && $string{3} == chr(0)) {
            $this->debugMessage("Connected to MQTT");
        } else {
            $msg = sprintf("Connection failed! Error: 0x%02x 0x%02x",
                ord($string{0}),ord($string{3}) );
            $this->debugMessage($msg);
            return false;
        }

        $this->timeSincePingReq = time();
        $this->timeSincePingResp = time();

        return true;
    }


    /**
     * Sets client crt and key files for client-side authentication
     *
     * @param string $crtFile Client certificate file
     * @param string $keyFile Client key file
     */
    function setClientCert($crtFile, $keyFile)
    {
        if(!file_exists($crtFile)) {
            $this->debugMessage("Client certificate not found");
            return;
        }

        if(!file_exists($keyFile)) {
            $this->debugMessage("Client key not found");
            return;
        }

        $this->clientCrt = $crtFile;
        $this->clientKey = $keyFile;
    }

    /**
     * Sets CAfile which is used to identify server
     *
     * @param string $caFile Client certificate file
     */
    function setCAFile($caFile)
    {
        if(!file_exists($caFile)) {
            $this->debugMessage("CA file not found");
            return;
        }

        $this->caFile = $caFile;
    }

    /**
     * Sets authentication details
     *
     * @param string $username Username
     * @param string $password Password
     */
    function setAuthDetails($username, $password)
    {
        $this->authUser = $username;
        $this->authPass = $password;
    }

    /**
     * Enables TLS connection and sets crypto protocol
     * Valid values: ssl, tls, tlsv1.0, tlsv1.1, tlsv1.2, sslv3
     * See this page for more info on values: http://php.net/manual/en/migration56.openssl.php
     *
     * @param string $protocol Set encryption protocol
     */
    function setCryptoProtocol($protocol)
    {
        if(!in_array($protocol, ["ssl","tls","tlsv1.1","tlsv1.2"])) {
            return;
        }
        $this->connMethod = $protocol;
    }

    /**
     * Loop to process data packets
     */
    function eventLoop()
    {
        // Socket not connected at all?
        if($this->socket == null) {
            return;
        }

        // Server closed connection?
        if(feof($this->socket)) {
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            $this->socket = null;
            return;
        }

        //
        $byte = $this->readBytes(1, true);
        if(strlen($byte) > 0) {
            $cmd = ord($byte);
            $bytes = ord($this->readBytes(1,true));

            $payload = "";
            if($bytes>0)
                $payload = $this->readBytes($bytes, false);

            switch( $cmd & 0xf0 ) {
                case 0xd0:      // PINGRESP
                    $this->debugMessage("Ping response received");
                    break;

                case 0x30:      // PUBLISH
                    $msg_qos = ( $cmd & 0x06 ) >> 1; // QoS = bits 1 & 2
                    $this->processMessage( $payload, $msg_qos );
                    break;

                case 0x40:  // PUBACK
                    $msg_qos = ( $cmd & 0x06 ) >> 1; // QoS = bits 1 & 2
                    $this->processPubAck( $payload, $msg_qos );
                    break;
            }

            $this->timeSincePingReq = time();
            $this->timeSincePingResp = time();
        }

        if( $this->timeSincePingReq < (time() - $this->keepAlive ) ) {
            $this->debugMessage("Nothing received for a while, pinging..");
            $this->sendPing();
        }


        if( $this->timeSincePingResp < (time() - ($this->keepAlive*2)) ) {
            $this->debugMessage("Not seen a package in a while, reconnecting..");
            stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
            $this->socket = null;
        }

    }

    /**
     * Subscribe to given MQTT topics
     *
     * @param array $topics Topics to subscribe to
     *
     * @return boolean Did subscribe work or not?
     */
    function subscribe($topics)
    {
        // This will fail, if socket is not connected
        if(!$this->socket) {
            $this->debugMessage("Subscribe failed, because socket is not connected");
            return false;
        }

        //
        $cnt = 2;

        // Create payload starting with packet ID
        $payload = chr($this->packet >> 8) . chr($this->packet & 0xff);

        // If for some reason topic is provided as string, convert it to array
        // If $topics is neither array nor string, refuse to continue
        if( !is_array($topics) && is_string($topics) ) {
            $topics = [ $topics => [ 'qos' => 1 ] ];
        } elseif( !is_array($topics) ) {
            return false;
        }

        //
        $numOfTopics = 0;
        foreach($topics as $topic=>$data) {
            // Topic data in wrong format?
            if( !is_array($data) || !isset($data['qos']) ) {
                continue;
            }

            //
            $payload.=$this->convertString($topic, $cnt);
            $payload.=chr($data['qos']); $cnt++;

            $this->topics[$topic]=$data;
            $numOfTopics++;
        }

        // If number of subscribed topics is 0, don't send the request
        if($numOfTopics == 0) {
            return false;
        }

        // Send SUBSCRIBE header & payload
        $header = chr(0x82).chr($cnt);
        fwrite($this->socket, $header, 2);
        fwrite($this->socket, $payload, $cnt);

        // Wait for SUBACK packet
        $resp_head = $this->readBytes(2, false);
        if( strlen($resp_head) != 2 || ord($resp_head{0}) != 0x90 ) {
            $this->debugMessage("Invalid SUBACK packet received (stage 1)");
            return false;
        }

        // Read remainder of the response
        $bytes = ord($resp_head{1});
        $resp_body = $this->readBytes($bytes, false);
        if( strlen($resp_body) < 2 ) {
            $this->debugMessage("Invalid SUBACK packet received (stage 2)");
            return false;
        }

        $package_id = ( ord($resp_body{0}) << 8 ) + ord($resp_body{1});
        if( $this->packet != $package_id ) {
            $this->debugMessage("SUBACK packet received for wrong message");
            return false;
        }

        // FIXME: Process the rest of the SUBACK payload

        //
        $this->packet++;
        return true;
    }

    /**
     * Closes connection to server by first sending DISCONNECT packet, and
     * then closing the stream socket
     */
    function close()
    {
        if(!$this->socket) {
            return;
        }

        $this->sendDisconnect();
        stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
        $this->socket = null;
    }

    /**
     * Sets verbosity level of messages
     *
     * @param int $level Verbosity level (0: silent , 1: verbose, 2: debug)
     */
    function setVerbose($level)
    {
        if ( $level == 1 || $level == 2 ) {
            $this->debug = true;
        }
    }

    /**
     * Gets queue of qos 1 messages that haven't been acknowledged by server
     */
    function getMessageQueue()
    {
        return $this->messageQueue;
    }

    /**
     * Publish message to server
     *
     * @param string $topic Topic to which message is published to
     * @param string $message Message to publish
     * @param int $qos QoS of message (0/1)
     * @param int $retain If set to 1 , server will try to retain the message
     *
     * @return boolean Did publish work or not
     */
    function publish($topic, $message, $qos, $retain = 0)
    {
        // Do nothing, if socket isn't connected
        if(!$this->socket) {
            return false;
        }

        // Sanity checks for QoS and retain values
        if( ( $qos != 0 && $qos != 1 ) || ($retain != 0 && $retain != 1 ) ) {
            return false;
        }

        //
        $bytes = 0;
        $payload = $this->convertString($topic,$bytes);

        // Add message identifier to QoS (1/2) packages
        if($qos > 0 ) {
            $payload .= chr($this->packet >> 8) . chr($this->packet & 0xff);
            $bytes+=2;
        }

        // Add Message to package and create header
        $payload .= $message;
        $bytes += strlen($message);
        $header = $this->createHeader( 0x30 + ($qos<<1) + $retain , $bytes );

        //
        fwrite($this->socket, $header, strlen($header));
        fwrite($this->socket, $payload, $bytes);

        // If message QoS = 1 , add message to queue
        if($qos==1) {
            $this->messageQueue[ $this->packet ] = [
                "topic" => $topic,
                "message" => $message,
                "qos" => $qos,
                "retain" => $retain,
                "time" => time(),
                "attempt" => 1
            ];
        }

        //
        $this->packet++;
        return true;
    }

    /**
     * Process puback messages sent by server
     *
     * @param string $msg Message
     * @param int $qos QoS of message
     */
    private function processPubAck ($payload, $qos)
    {
        if( strlen($payload) < 2 ) {
            $this->debugMessage("Malformed PUBACK package received");
            return false;
        }

        $package_id = ( ord($payload{0}) << 8 ) + ord($payload{1});
        if( !isset($this->messageQueue[$package_id]) ) {
            $this->debugMessage("Received PUBACK for package we didn't sent?");
            return false;
        }

        unset( $this->messageQueue[$package_id] );
    }

    /**
     * Process publish messages sent by server
     *
     * @param string $msg Message
     * @param int $qos QoS of message
     */
    private function processMessage($msg, $qos)
    {
        // Package starts with topic
        $tlen = (ord($msg{0})<<8) + ord($msg{1});
        $msg_topic = substr($msg,2,$tlen);

        // QoS 1 and 2 packets also contain identifier
        $msg_id = null;
        if($qos == 0) {
            $msg = substr($msg,$tlen+2);
        } else {
            $msg_id = substr($msg,$tlen+2,2);
            $msg = substr($msg,$tlen+4);
        }

        // Then comes the message itself
        $found = false;
        foreach($this->topics as $topic=>$data) {

            $t_topic = str_replace("+","[^/]*", $topic);
            $t_topic = str_replace("/","\/",$t_topic);
            $t_topic = str_replace("$","\$",$t_topic);
            $t_topic = str_replace("#",".*",$t_topic);

            if(!preg_match("/^".$t_topic."$/", $msg_topic)) {
                continue;
            }

            $found = true;

            $this->debugMessage("Packet received (QoS: ".$qos." ; topic: ".$msg_topic." ; msg: ".$msg.")",2);

            // Is callback for this topic set?
            if(isset($data["function"]) && is_callable($data["function"])) {
                call_user_func($data["function"], $msg_topic, $msg, $qos);
            }
        }

        //
        if(!$found) {
            $this->debugMessage("Package received, but it doesn't match subscriptions");
        }

        // QoS 1 package requires PUBACK packet
        if($qos==1) {
            $this->debugMessage("Packet with QoS 1 received, sending PUBACK");
            $payload = chr(0x40).chr(0x02).$msg_id;
            fwrite($this->socket, $payload, 4);
        }

        // QoS 2 package requires PUBRECT packet, but we won't give it :)
        if($qos==2) {
            // FIXME
            $this->debugMessage("Packet with QoS 2 received, but feature is not implemented");
        }
    }

    /**
     * Handles debug messages
     *
     * @param string $msg Message
     * @param int $level Logging level
     */
    private function debugMessage($msg, $level=1)
    {
        if(!$this->debug || $this->debug < $level) {
            return;
        }

        echo "libmqtt: ".$msg."\n";
    }

    /**
     * Create MQTT header, with command and length
     *
     * @param int $cmd Command to send
     * @param int $bytes Number of bytes in the package
     *
     * @return string Header to send
     */
    private function createHeader($cmd, $bytes)
    {
        $retval = chr($cmd);

        $bytes_left = $bytes;
        do {
            $byte = $bytes_left % 128;
            $bytes_left >>= 7;

            if($bytes_left>0) {
              $byte = $byte | 0x80;
            }

            $retval.=chr($byte);

        } while($bytes_left>0);

        return $retval;
    }

    /**
     * Writes given string to MQTT string format
     *
     * @param string $data String to convert
     * @param int $cnt Reference to length counter
     *
     * @return string String in MQTT format
     */
    private function convertString($data, &$cnt)
    {
        $len = strlen($data);
        $cnt+=$len+2;
        $retval = chr($len>>8).chr($len&0xff).$data;
        return $retval;
    }

    /**
     * Read x bytes from socket
     *
     * @param int $bytes Number of bytes to read
     * @param boolean $noBuffer If true, use only direct fread
     */
    private function readBytes($bytes, $noBuffer)
    {
        if(!$this->socket) {
            return "";
        }

        if($noBuffer) {
            return fread($this->socket, $bytes);
        }

        $bytes_left = $bytes;
        $retval = "";
        while( !feof($this->socket) && $bytes_left > 0 ) {
            $res = fread($this->socket, $bytes_left);
            $retval.=$res;
            $bytes_left-=strlen($res);
        }

        return $retval;
    }

    /**
     * Sends PINGREQ packet to server
     */
    private function sendPing()
    {
        $this->timeSincePingReq = time();
        $payload = chr(0xc0).chr(0x00);
        fwrite($this->socket, $payload, 2);

        $this->debugMessage("PING sent");
    }

    /**
     * Sends DISCONNECT packet to server
     */
    private function sendDisconnect()
    {
        if(!$this->socket || feof($this->socket))
            return;

        $payload = chr(0xe0).chr(0x00);
        fwrite($this->socket, $payload, 2);

        $this->debugMessage("DISCONNECT sent");
    }
}
