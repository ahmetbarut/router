<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class RequestTests extends TestCase
{

    public function testGet()
    {
        $client = new Client();
        $response = $client->get('http://localhost:8000/222');
        $this->assertEquals(200, $response->getStatusCode());

    }
    
}
