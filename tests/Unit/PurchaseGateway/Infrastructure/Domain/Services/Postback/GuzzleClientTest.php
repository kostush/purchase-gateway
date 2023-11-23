<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Postback;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback\GuzzleClient;
use Tests\UnitTestCase;

/**
 * Class GuzzleClientTest
 * @package Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services
 * @group   postback
 *
 * Exceptions source: http://docs.guzzlephp.org/en/latest/quickstart.html#exceptions
 */
class GuzzleClientTest extends UnitTestCase
{
    /**
     * @var PostbackResponseDto
     */
    private $dto;

    /**
     * @var string
     */
    private $url;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dto = $this->createMock(PostbackResponseDto::class);
        $this->url = 'http://localhost';
    }

    /**
     * @param Response|RequestException $response Either a regular response or an exception.
     *
     * @return Client
     */
    private function createGuzzleClient($response)
    {
        return new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_true_when_response_code_is_100_level()
    {
        $response = new Response(102);
        $this->assertTrue((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_true_when_response_code_is_200_level()
    {
        $response = new Response(200);
        $this->assertTrue((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_true_when_response_code_is_300_level()
    {
        $response = new Response(301);
        $this->assertTrue((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_when_response_is_500_level()
    {
        $response = new ServerException('500 Error', new Request('POST', $this->url));
        $this->assertFalse((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_when_response_is_400_level()
    {
        $response = new ClientException('400 Error', new Request('POST', $this->url));
        $this->assertFalse((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_when_response_is_a_network_error()
    {
        $response = new ConnectException('Network Error', new Request('POST', $this->url));
        $this->assertFalse((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_when_response_is_too_many_redirects()
    {
        $response = new TooManyRedirectsException('Too Many Redirects', new Request('POST', $this->url));
        $this->assertFalse((new GuzzleClient($this->createGuzzleClient($response)))->post($this->dto, $this->url));
    }
}
