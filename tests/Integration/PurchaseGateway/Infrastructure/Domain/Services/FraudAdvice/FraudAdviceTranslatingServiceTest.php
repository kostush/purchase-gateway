<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use PHPUnit\Framework\MockObject\MockObject;
use ProbillerNG\FraudServiceClient\Api\FraudServiceApi;
use ProbillerNG\FraudServiceClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceTranslator;
use Tests\IntegrationTestCase;

class FraudAdviceTranslatingServiceTest extends IntegrationTestCase
{
    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $zip;

    /**
     * @var string
     */
    private $bin;

    /**
     * @var InlineResponse200
     */
    private $response;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->ip    = '127.0.0.1';
        $this->email = 'test@email.com';
        $this->zip   = 'H0H0H0';
        $this->bin   = '123456';

        $this->response = new InlineResponse200(
            [
                'captcha'   => true,
                'blacklist' => true
            ]
        );
    }

    /**
     * @test
     * @return FraudAdvice
     *
     * @throws \Exception
     */
    public function it_should_return_a_correct_fraud_advice()
    {
        $client = $this->createMock(FraudServiceApi::class);
        $client->method('getFraudAdviceForSite')
            ->willReturn(
                new InlineResponse200(['captcha' => true, 'blacklist' => true])
            );

        /** @var FraudAdviceTranslatingService | MockObject $service */
        $service = $this->getMockBuilder(FraudAdviceTranslatingService::class)
            ->setConstructorArgs(
                [
                    new FraudAdviceAdapter(
                        new FraudAdviceClient($client),
                        new FraudAdviceTranslator()
                    )
                ]
            )
            ->setMethods(null)
            ->getMock();

        $fraudAdvice = $service->retrieveAdvice(
            SiteId::create(),
            ['ip' => $this->ip, 'email' => $this->email, 'zip' => $this->zip, 'bin' => $this->bin],
            FraudAdvice::FOR_INIT,
            SessionId::create()
        );

        $this->assertInstanceOf(FraudAdvice::class, $fraudAdvice);

        return $fraudAdvice;
    }
}
