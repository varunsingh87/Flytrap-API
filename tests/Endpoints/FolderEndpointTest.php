<?php 

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class FolderEndpointTest extends TestCase {
    private $http;

    public function setUp() : void {
        $this->http = new Client(["base_uri" => "https://api.audio.borumtech.com/api/v1/"]);
    }

    public function testPost() {
        $response = $this->http->post('folder');

        $this->assertEquals(200, $response->getStatusCode());

        $contentType = $response->getHeaders()["Content-Type"][0];
        $this->assertEquals("application/json; charset=UTF-8", $contentType);
    }
}

?>