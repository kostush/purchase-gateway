<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList;

use Google\Protobuf\GPBEmpty;
use Grpc\UnaryCall;
use Probiller\Service\Config\CreditCardBlacklistStatus;
use Probiller\Service\Config\ProbillerConfigClient;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\ErrorClassification;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use stdClass;
use Tests\IntegrationTestCase;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_UNAVAILABLE;

class CCForBlackListTranslatingServiceTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_add_card_to_blacklist(): void
    {
        $responseStatus          = new stdClass();
        $responseStatus->code    = STATUS_OK;
        $responseStatus->details = '';

        $unaryCallMock = $this->createMock(UnaryCall::class);
        $unaryCallMock->method('wait')->willReturn(
            [
                new GPBEmpty(),
                $responseStatus
            ]
        );

        $configServiceClientMock = $this->createMock(ProbillerConfigClient::class);
        $configServiceClientMock->method('AddCreditCardBlacklist')->willReturn($unaryCallMock);
        $configServiceClientMock->expects($this->once())->method('AddCreditCardBlacklist');

        $ccForBlackListTranslatingService = new CCForBlackListTranslatingService(
            new ConfigService($configServiceClientMock)
        );

        $cardMock = $this->faker->creditCardNumber;

        $errorClassificationMock = $this->createMock(ErrorClassification::class);
        $errorClassificationMock->method('toArray')->willReturn(['errorType' => 'Hard']);

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('errorClassification')->willReturn($errorClassificationMock);

        $ccForBlackListTranslatingService->addCCForBlackList(
            substr($cardMock, 0, 6),
            substr($cardMock, -4),
            $this->faker->month,
            $this->faker->year,
            $this->faker->uuid,
            $transaction
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_not_add_card_to_blacklist(): void
    {
        $configServiceClientMock = $this->createMock(ProbillerConfigClient::class);
        $configServiceClientMock->expects($this->never())->method('AddCreditCardBlacklist');

        $ccForBlackListTranslatingService = new CCForBlackListTranslatingService(
            new ConfigService($configServiceClientMock)
        );

        $cardMock = $this->faker->creditCardNumber;

        $errorClassificationMock = $this->createMock(ErrorClassification::class);
        $errorClassificationMock->method('toArray')->willReturn(['errorType' => 'Soft']);

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('errorClassification')->willReturn($errorClassificationMock);

        $ccForBlackListTranslatingService->addCCForBlackList(
            substr($cardMock, 0, 6),
            substr($cardMock, -4),
            $this->faker->month,
            $this->faker->year,
            $this->faker->uuid,
            $transaction
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_if_response_code_is_not_ok(): void
    {
        $responseStatus          = new stdClass();
        $responseStatus->code    = STATUS_UNAVAILABLE;
        $responseStatus->details = '';

        $unaryCallMock = $this->createMock(UnaryCall::class);
        $unaryCallMock->method('wait')->willReturn(
            [
                null,
                $responseStatus
            ]
        );

        $configServiceClientMock = $this->createMock(ProbillerConfigClient::class);
        $configServiceClientMock->method('CheckCreditCardBlacklist')->willReturn($unaryCallMock);

        $ccForBlackListTranslatingService = new CCForBlackListTranslatingService(
            new ConfigService($configServiceClientMock)
        );

        $cardMock = $this->faker->creditCardNumber;

        $response = $ccForBlackListTranslatingService->checkCCForBlacklist(
            substr($cardMock, 0, 6),
            substr($cardMock, -4),
            $this->faker->month,
            $this->faker->year,
            $this->faker->uuid
        );

        $this->assertFalse($response);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_true_if_card_is_black_listed(): void
    {
        $responseStatus          = new stdClass();
        $responseStatus->code    = STATUS_OK;
        $responseStatus->details = '';

        $unaryCallMock = $this->createMock(UnaryCall::class);
        $unaryCallMock->method('wait')->willReturn(
            [
                (new CreditCardBlacklistStatus())->setIsBlacklisted(true),
                $responseStatus
            ]
        );

        $configServiceClientMock = $this->createMock(ProbillerConfigClient::class);
        $configServiceClientMock->method('CheckCreditCardBlacklist')->willReturn($unaryCallMock);

        $ccForBlackListTranslatingService = new CCForBlackListTranslatingService(
            new ConfigService($configServiceClientMock)
        );

        $cardMock = $this->faker->creditCardNumber;

        $response = $ccForBlackListTranslatingService->checkCCForBlacklist(
            substr($cardMock, 0, 6),
            substr($cardMock, -4),
            $this->faker->month,
            $this->faker->year,
            $this->faker->uuid
        );

        $this->assertTrue($response);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_false_if_card_is_not_black_listed(): void
    {
        $responseStatus          = new stdClass();
        $responseStatus->code    = STATUS_OK;
        $responseStatus->details = '';

        $unaryCallMock = $this->createMock(UnaryCall::class);
        $unaryCallMock->method('wait')->willReturn(
            [
                (new CreditCardBlacklistStatus())->setIsBlacklisted(false),
                $responseStatus
            ]
        );

        $configServiceClientMock = $this->createMock(ProbillerConfigClient::class);
        $configServiceClientMock->method('CheckCreditCardBlacklist')->willReturn($unaryCallMock);

        $ccForBlackListTranslatingService = new CCForBlackListTranslatingService(
            new ConfigService($configServiceClientMock)
        );

        $cardMock = $this->faker->creditCardNumber;

        $response = $ccForBlackListTranslatingService->checkCCForBlacklist(
            substr($cardMock, 0, 6),
            substr($cardMock, -4),
            $this->faker->month,
            $this->faker->year,
            $this->faker->uuid
        );

        $this->assertFalse($response);
    }
}
