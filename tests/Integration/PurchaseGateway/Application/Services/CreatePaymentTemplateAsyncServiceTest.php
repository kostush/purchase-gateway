<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Application\Services\CreatePaymentTemplateAsyncService;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProBillerNG\ServiceBus\ServiceBus;
use ProBillerNG\ServiceBus\Event as MessageEvent;
use Tests\IntegrationTestCase;

class CreatePaymentTemplateAsyncServiceTest extends IntegrationTestCase
{
    /** @var string */
    private $transactionId;

    /** @var string */
    private $first6;

    /** @var string */
    private $last4;

    /** @var int */
    private $cardExpirationMonth;

    /** @var int */
    private $cardExpirationYear;

    /** @var string */
    private $cardHash;

    /** @var string */
    private $memberId;

    /** @var string */
    private $purchaseId;

    public function setUp(): void
    {
        parent::setUp();
        $this->transactionId       = $this->faker->uuid;
        $this->first6              = (string) random_int(400000, 499999);
        $this->last4               = (string) random_int(1000, 9999);
        $this->cardExpirationMonth = random_int(01, 12);
        $this->cardExpirationYear  = random_int(2022, 3000);
        $this->cardHash            = $this->faker->sha256;
        $this->memberId            = $this->faker->uuid;
        $this->purchaseId          = $this->faker->uuid;
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_create_async_event_when_data_is_correct(): void
    {
        $transactionService = $this->createMock(TransactionService::class);

        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBus        = $this->createMock(ServiceBus::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);

        $transactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionResult->method('cardHash')->willReturn($this->cardHash);
        $transactionResult->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('first6')->willReturn($this->first6);
        $transactionInformation->method('last4')->willReturn($this->last4);
        $transactionInformation->method('cardExpirationMonth')->willReturn($this->cardExpirationMonth);
        $transactionInformation->method('cardExpirationYear')->willReturn($this->cardExpirationYear);
        $transactionInformation->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $transactionInformation->method('createdAt')->willReturn(new \DateTimeImmutable("now"));
        $transactionInformation->method('transactionId')->willReturn($this->transactionId);

        $transactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $createPaymentTemplateAsyncService = new CreatePaymentTemplateAsyncService(
            $serviceBusFactory,
            $transactionService
        );

        $serviceBus->expects($this->atLeastOnce())
            ->method('publish')
            ->with($this->callback(function ($argument) {
                if ($argument instanceof MessageEvent) {
                    return ($argument->body()['lastFour'] == $this->last4) && ($argument->body()['firstSix'] == $this->first6);
                }

                return false;
            }));

        $createPaymentTemplateAsyncService->create(
            (string) $transactionInformation->transactionId(),
            (string) $this->purchaseId,
            (string) $this->memberId
        );
    }
}