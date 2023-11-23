<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Postback;

use App\Jobs\ClientPostbackJob;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback\GuzzleClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback\LumenJob;
use Tests\UnitTestCase;

/**
 * Class LumenJobTest
 * @package Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services
 * @group   postback
 */
class LumenJobTest extends UnitTestCase
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
     * @var GuzzleClient|MockObject
     */
    private $guzzleClient;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->dto          = $this->createMock(PostbackResponseDto::class);
        $this->url          = 'http://localhost';
        $this->guzzleClient = $this->createMock(GuzzleClient::class);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_true_when_client_post_succeed()
    {
        $this->guzzleClient->method('post')->willReturn(true);

        $this->assertTrue((new LumenJob($this->guzzleClient))->send($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_client_post_fail()
    {
        $this->guzzleClient->method('post')->willReturn(false);

        $this->assertFalse((new LumenJob($this->guzzleClient))->send($this->dto, $this->url));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_queue_the_job_when_valid_url_is_provided()
    {
        Queue::fake();

        (new LumenJob($this->guzzleClient))->queue($this->dto, $this->url);

        Queue::assertPushed(ClientPostbackJob::class);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_queue_the_job_when_url_is_not_provided()
    {
        Queue::fake();

        (new LumenJob($this->guzzleClient))->queue($this->dto, null);

        Queue::assertNotPushed(ClientPostbackJob::class);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_queue_the_job_when_invalid_url_is_provided()
    {
        Queue::fake();

        (new LumenJob($this->guzzleClient))->queue($this->dto, "null");

        Queue::assertNotPushed(ClientPostbackJob::class);
    }
}
