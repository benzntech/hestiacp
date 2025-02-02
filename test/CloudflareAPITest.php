<?php

use PHPUnit\Framework\TestCase;
use Hestia\System\CloudflareAPI;

class CloudflareAPITest extends TestCase {
    private $api;
    private $mockToken = 'test_token';

    protected function setUp(): void {
        $this->api = new CloudflareAPI($this->mockToken);
    }

    /**
     * @test
     * @group unit
     */
    public function it_should_verify_token() {
        // Mock the request method using reflection
        $method = new ReflectionMethod(CloudflareAPI::class, 'request');
        $method->setAccessible(true);

        // Test successful verification
        $mockResponse = [
            'success' => true,
            'result' => ['status' => 'active']
        ];

        $this->assertEquals(true, $this->api->verifyToken());
    }

    /**
     * @test
     * @group unit
     */
    public function it_should_list_zones() {
        $mockZones = [
            [
                'id' => 'zone1',
                'name' => 'example.com',
                'status' => 'active'
            ]
        ];

        $mockResponse = [
            'success' => true,
            'result' => $mockZones
        ];

        // Assert zones are returned correctly
        $this->assertEquals($mockZones, $this->api->listZones());
    }

    /**
     * @test
     * @group unit
     */
    public function it_should_manage_dns_records() {
        $zoneId = 'zone1';
        $recordId = 'record1';
        $record = [
            'type' => 'A',
            'name' => 'test.example.com',
            'content' => '1.2.3.4',
            'proxied' => true
        ];

        // Test create record
        $this->assertNotFalse($this->api->createDnsRecord($zoneId, $record));

        // Test update record
        $this->assertNotFalse($this->api->updateDnsRecord($zoneId, $recordId, $record));

        // Test delete record
        $this->assertTrue($this->api->deleteDnsRecord($zoneId, $recordId));
    }

    /**
     * @test
     * @group unit
     */
    public function it_should_manage_ssl_configuration() {
        $zoneId = 'zone1';
        
        // Test get SSL config
        $mockConfig = [
            'value' => 'full',
            'certificate_status' => 'active'
        ];
        $this->assertEquals($mockConfig, $this->api->getSSLConfig($zoneId));

        // Test update SSL mode
        $this->assertNotFalse($this->api->updateSSLConfig($zoneId, 'strict'));
    }

    /**
     * @test
     * @group unit
     */
    public function it_should_manage_cache() {
        $zoneId = 'zone1';
        
        // Test purge everything
        $this->assertTrue($this->api->purgeCache($zoneId));

        // Test purge specific files
        $files = ['https://example.com/style.css'];
        $this->assertTrue($this->api->purgeCache($zoneId, $files));
    }

    /**
     * @test
     * @group unit
     */
    public function it_should_manage_development_mode() {
        $zoneId = 'zone1';
        
        // Test enable dev mode
        $this->assertNotFalse($this->api->updateDevMode($zoneId, true));

        // Test disable dev mode
        $this->assertNotFalse($this->api->updateDevMode($zoneId, false));
    }

    /**
     * @test
     * @group integration
     */
    public function it_should_handle_api_errors() {
        // Test with invalid token
        $api = new CloudflareAPI('invalid_token');
        $this->assertFalse($api->verifyToken());
        $this->assertNotEmpty($api->getLastError());

        // Test with invalid zone ID
        $this->assertFalse($api->getSSLConfig('invalid_zone'));
        $this->assertNotEmpty($api->getLastError());
    }
}