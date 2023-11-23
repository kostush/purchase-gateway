<?php

namespace ConfigService;

use Tests\SystemTestCase;

class CorsSetupFromConfigServiceTest extends SystemTestCase
{
    /**
     * @test
     */
    public function it_should_return_not_allowed_when_an_origin_headers_is_not_authorized()
    {
        $headers = [
            'Content-Type' => 'application/json',
            'x-api-key'    => $_ENV['PAYSITES_API_KEY'],
            'Origin'       => 'notauthorizeddomain.com'
        ];

        $payload = [];

        $result = $this->json(
            'POST',
            '/api/v1/purchase/process',
            $payload,
            $headers
        );

        $this->assertEquals(403, $result->response->status());
        $this->assertEquals('Not allowed in CORS policy.', $result->response->content());
    }

    /**
     * @test
     */
    public function it_should_not_return_not_allowed_when_an_origin_headers_is_authorized()
    {
        $configServiceDomain = 'probiller.mofosexpremium.com';

        $headers = [
            'Content-Type' => 'application/json',
            'Origin'       => $configServiceDomain,
            'x-api-key'    => $_ENV['PAYSITES_API_KEY'],
        ];

        $payload = [];

        $result = $this->json(
            'POST',
            '/api/v1/purchase/process',
            $payload,
            $headers
        );

        $this->assertNotEquals(403, $result->response->status());
        $this->assertNotEquals('Not allowed in CORS policy.', $result->response->content());
        $this->assertEquals('{"code":0,"error":"Missing auth token"}', $result->response->content());
    }

    /**
     * @test
     */
    public function it_should_return_not_allowed_when_an_origin_headers_is_not_authorized_with_option()
    {
        $headers = [
            'Content-Type'                  => 'application/json',
            'Access-Control-Request-Method' => 'POST',
            'x-api-key'                     => $_ENV['PAYSITES_API_KEY'],
            'Origin'                        => 'notauthorizeddomain.com'
        ];
        $payload = [];
        $result  = $this->json(
            'OPTIONS',
            '/api/v1/purchase/process',
            $payload,
            $headers
        );
        $this->assertEquals(403, $result->response->getStatusCode());
        $this->assertEquals('Origin not allowed', $result->response->getContent());
    }

    /**
     * @test
     */
    public function it_should_return_allowed_when_an_origin_headers_is_authorized_with_option()
    {
        $headers = [
            'Content-Type'                  => 'application/json',
            'Access-Control-Request-Method' => 'POST',
            'x-api-key'                     => $_ENV['PAYSITES_API_KEY'],
            'Origin'                        => 'probiller.mofosexpremium.com'
        ];
        $payload = [];
        $result  = $this->json(
            'OPTIONS',
            '/api/v1/purchase/process',
            $payload,
            $headers
        );
        $this->assertEquals(204, $result->response->getStatusCode());
        $this->assertEquals('', $result->response->getContent());
    }
}