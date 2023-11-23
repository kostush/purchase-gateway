<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use PHPUnit\Framework\MockObject\MockObject;
use ProbillerNG\FraudServiceClient\ApiException;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayload;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayloadBlacklist;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayloadCaptcha;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceTranslationException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceTranslator;
use Tests\UnitTestCase;

class FraudAdviceAdapterTest extends UnitTestCase
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
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->ip    = '127.0.0.1';
        $this->email = 'test@email.com';
        $this->zip   = 'H0H0H0';
        $this->bin   = '123456';
    }

    /**
     * @test
     * @return void
     *
     * @throws FraudAdviceApiException
     * @throws FraudAdviceTranslationException
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_throw_fraud_advice_api_exception_if_api_exception_encountered()
    {
        $this->expectException(FraudAdviceApiException::class);

        $client = $this->createMock(FraudAdviceClient::class);
        $client->method('retrieveAdvice')->willThrowException(new ApiException());

        $translator = $this->createMock(FraudAdviceTranslator::class);

        /** @var FraudAdviceAdapter|MockObject $adapter */
        $adapter = $this->getMockBuilder(FraudAdviceAdapter::class)
            ->setConstructorArgs(
                [
                    $client,
                    $translator
                ]
            )
            ->setMethods(null)
            ->getMock();

        $adapter->retrieveAdvice(
            SiteId::create(),
            ['ip' => $this->ip, 'zip' => $this->zip],
            FraudAdvice::FOR_INIT,
            SessionId::create()
        );
    }

    /**
     * @test
     * @return void
     *
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws FraudAdviceApiException
     * @throws FraudAdviceTranslationException
     * @throws \Exception
     */
    public function it_should_call_client_with_correct_payload()
    {
        $request = new FraudAdvicePayload();
        $request->setCaptcha(new FraudAdvicePayloadCaptcha(['ip' => $this->ip, 'zip' => $this->zip]));
        $request->setBlacklist(new FraudAdvicePayloadBlacklist(['ip' => $this->ip, 'zip' => $this->zip]));

        $sessionId = SessionId::create();

        $request->setSessionId((string) $sessionId);

        $siteId = SiteId::create();

        $client = $this->createMock(FraudAdviceClient::class);
        $client->expects($this->once())
            ->method('retrieveAdvice')
            ->with($siteId, $request);

        $translator = $this->createMock(FraudAdviceTranslator::class);
        $translator->method('translate')
            ->willReturn(FraudAdvice::create());

        /** @var FraudAdviceAdapter|MockObject $adapter */
        $adapter = $this->getMockBuilder(FraudAdviceAdapter::class)
            ->setConstructorArgs(
                [
                    $client,
                    $translator
                ]
            )
            ->setMethods(null)
            ->getMock();

        $adapter->retrieveAdvice(
            $siteId,
            ['ip' => $this->ip, 'zip' => $this->zip],
            FraudAdvice::FOR_INIT,
            $sessionId
        );
    }
}
