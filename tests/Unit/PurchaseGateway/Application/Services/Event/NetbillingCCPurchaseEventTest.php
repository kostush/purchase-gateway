<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\NetbillingCCPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NetbillingCCRetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;
use Tests\UnitTestCase;

class NetbillingCCPurchaseEventTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $siteTag;

    /**
     * @var string
     */
    private $merchantPassword;

    /**
     * Init
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->accountId        = $_ENV['NETBILLING_ACCOUNT_ID'];
        $this->siteTag          = $_ENV['NETBILLING_SITE_TAG'];
        $this->merchantPassword = $_ENV['NETBILLING_MERCHANT_PASSWORD'];
    }

    /**
     * @return NetbillingCCRetrieveTransactionResult
     */
    private function createNetbillingCCRetrieveTransactionResultMocks()
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn(NetbillingBiller::BILLER_ID);
        $retrieveTransaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getSiteId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getCurrency')->willReturn('RON');
        $retrieveTransaction->method('getPaymentType')->willReturn('cc');
        $retrieveTransaction->method('getCardHash')->willReturn('123456789');
        $retrieveTransaction->method('getCardDescription')->willReturn('CREDIT');
        $retrieveTransaction->method('getBillerTransactions')->willReturn(
            [$this->createMock(RetrieveTransactionBillerTransactions::class)]
        );


        $memberInformation = $this->createMock(MemberInformation::class);

        $ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);

        $billerFields = NetbillingBillerFields::create(
            $this->accountId,
            $this->siteTag,
            null,
            $this->merchantPassword
        );

        $netbillingCCRetrieveTransactionResult = new NetbillingCCRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            $billerFields
        );

        return $netbillingCCRetrieveTransactionResult;
    }

    /**
     * @test
     * @return NetbillingCCPurchaseImportEvent
     * @throws Exception
     * @throws UnknownBillerIdException
     * @throws \Exception
     */
    public function it_should_return_a_netbilling_cc_purchase_event_object_if_correct_data_is_sent()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $netbillingCCPurchaseEvent = new NetbillingCCPurchaseImportEvent(
            $this->createNetbillingCCRetrieveTransactionResultMocks(),
            $purchaseProcessedEvent,
            null
        );

        $this->assertInstanceOf(NetbillingCCPurchaseImportEvent::class, $netbillingCCPurchaseEvent);
        return $netbillingCCPurchaseEvent;
    }

    /**
     * @test
     * @param NetbillingCCPurchaseImportEvent $netbillingCCPurchaseEvent NetbillingCCPurchaseEvent
     * @depends it_should_return_a_netbilling_cc_purchase_event_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_account_id(NetbillingCCPurchaseImportEvent $netbillingCCPurchaseEvent)
    {
        $this->assertEquals($this->accountId, $netbillingCCPurchaseEvent->accountId());
    }
}
