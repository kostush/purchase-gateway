<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeTypeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsTranslator;
use Tests\UnitTestCase;

class FraudAdviceCsAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_fraud_advice_code_cs_api_exception_if_api_exception_encountered()
    {
        $this->expectException(FraudAdviceCsCodeApiException::class);

        $client = $this->createMock(FraudAdviceCsClient::class);
        $client->method('retrieve')->willThrowException(new FraudAdviceCsCodeApiException());

        $translator = $this->createMock(FraudAdviceCsTranslator::class);

        /** @var FraudAdviceCsAdapter|MockObject $adapter */
        $adapter = $this->getMockBuilder(FraudAdviceCsAdapter::class)
            ->setConstructorArgs(
                [
                    $client,
                    $translator
                ]
            )
            ->setMethods(null)
            ->getMock();

        $adapter->retrieveAdvice(
            $this->createMock(PaymentTemplateCollection::class),
            $this->faker->uuid
        );
    }
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_fraud_advice_code_cs_type_exception_if_api_exception_encountered()
    {
        $this->expectException(FraudAdviceCsCodeTypeException::class);

        $client = $this->createMock(FraudAdviceCsClient::class);
        $client->method('retrieve')->willReturn([]);

        /** @var FraudAdviceCsAdapter|MockObject $adapter */
        $adapter = $this->getMockBuilder(FraudAdviceCsAdapter::class)
            ->setConstructorArgs(
                [
                    $client,
                    new FraudAdviceCsTranslator()
                ]
            )
            ->setMethods(null)
            ->getMock();

        $adapter->retrieveAdvice(
            $this->createMock(PaymentTemplateCollection::class),
            $this->faker->uuid
        );
    }
}
