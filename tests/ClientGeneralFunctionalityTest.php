<?php

use LibMQTT\Client;
use PHPUnit\Framework\TestCase;

class ClientGeneralFunctionalityTest extends TestCase
{
    protected $client;

    protected function SetUp()
    {
        $this->client = new Client('localhost', 1883, 'phpUnitClient');
    }

    protected function TearDown()
    {
        $this->client = null;
    }

    public function providerSetInvalidKeepAliveData()
    {
        return [
            [ -1 ],
            [ (15 + (12 * 60) + (18 * 60 * 60)) + 2 ],
            [ [] ],
            [ 'a string'] ,
        ];
    }

    /**
     * @dataProvider providerSetInvalidKeepAliveData
     * @param $value
     */
    public function testSetInvalidKeepAliveData($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->client->setKeepAlive($value);
    }

    public function testValidKeepAliveData()
    {
        $client = $this->client->setKeepAlive(60);
        $this->assertInstanceOf(Client::class, $client);
    }
}
