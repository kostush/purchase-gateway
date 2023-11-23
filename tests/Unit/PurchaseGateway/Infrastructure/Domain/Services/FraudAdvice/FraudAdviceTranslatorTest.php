<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProbillerNG\FraudServiceClient\Model\Error;
use ProbillerNG\FraudServiceClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceTranslationException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\FraudAdviceTranslator;
use Tests\UnitTestCase;

class FraudAdviceTranslatorTest extends UnitTestCase
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
     * @var FraudAdviceTranslator
     */
    private $translator;

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

        $this->translator = new FraudAdviceTranslator();

        $this->response = new InlineResponse200(
            [
                'captcha'   => true,
                'blacklist' => true
            ]
        );
    }

    /**
     * @test
     * @return void
     *
     * @throws FraudAdviceTranslationException
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_translation_exception_if_response_is_not_correct_type()
    {
        $this->expectException(FraudAdviceTranslationException::class);

        $this->translator->translate(new Error(), [], FraudAdvice::FOR_INIT);
    }

    /**
     * @test
     * @return FraudAdvice
     *
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws FraudAdviceTranslationException
     */
    public function it_should_return_a_fraud_advice_object_for_init()
    {
        $fraudAdvice = $this->translator->translate(
            $this->response,
            [
                'ip' => $this->ip
            ],
            FraudAdvice::FOR_INIT
        );

        $this->assertInstanceOf(FraudAdvice::class, $fraudAdvice);

        return $fraudAdvice;
    }

    /**
     * @test
     * @depends it_should_return_a_fraud_advice_object_for_init
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_mark_captcha_advised_on_advice(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @depends it_should_return_a_fraud_advice_object_for_init
     * @param FraudAdvice $fraudAdvice Fraud advice
     * @return void
     */
    public function it_should_mark_blacklisted_on_advice(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isBlacklistedOnInit());
    }

    /**
     * @test
     * @return void
     *
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws FraudAdviceTranslationException
     */
    public function it_should_return_a_fraud_advice_object_for_process()
    {
        $this->assertInstanceOf(
            FraudAdvice::class,
            $this->translator->translate(
                $this->response,
                [
                    'email' => $this->email,
                    'zip'   => $this->zip,
                    'bin'   => $this->bin,
                ],
                FraudAdvice::FOR_PROCESS
            )
        );
    }
}
