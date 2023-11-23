<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RedirectToUrl;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RedirectToFallbackProcessor;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionProcessFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\CascadeBillersExhausted;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Tests\UnitTestCase;

class ProcessPurchaseGeneralHttpDTOTest extends UnitTestCase
{
    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cryptService   = $this->createMock(CryptService::class);
        $this->tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface       = $this->createMock(JsonWebToken::class);
        $this->tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $this->tokenGenerator->method('generateWithGenericKey')->willReturn($tokenInterface);
    }

    /**
     * @test
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_not_successful()
    {
        $memberId   = $this->mockUUIDForClass(MemberId::class);
        $purchaseId = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId  = $this->faker->uuid;

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('state')->willReturn(Valid::create());

        $purchaseProcess->method('state')->willReturn($this->createMock(Valid::class));
        $purchaseProcess->method('memberId')->willReturn((string) $memberId);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('purchaseId')->willReturn((string) $purchaseId);
        $purchaseProcess->method('isCurrentBillerAvailablePaymentsMethods')->willReturn(false);

        $expected = [
            'success'          => false,
            'digest'           => '',
            'memberId'         => (string) $memberId,
            'purchaseId'       => (string) $purchaseId,
            'sessionId'        => (string) $sessionId,
            'nextAction'       => RenderGateway::create()->toArray()
        ];

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );
        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_not_successful_next_biller_is_third_party_and_redirect_url_is_missing(
    ): void
    {
        $memberId   = $this->mockUUIDForClass(MemberId::class);
        $purchaseId = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId  = $this->faker->uuid;

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();

        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new EpochBiller(),
                ]
            ),
            new RocketgateBiller(),
            2
        );

        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('state')->willReturn(Valid::create());

        $purchaseProcess->method('memberId')->willReturn((string) $memberId);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('purchaseId')->willReturn((string) $purchaseId);
        $purchaseProcess->method('cascade')->willReturn($cascade);

        $expected = [
            'success'    => false,
            'digest'     => '',
            'memberId'   => (string) $memberId,
            'purchaseId' => (string) $purchaseId,
            'sessionId'  => $sessionId,
            'nextAction' => RestartProcess::create('Missing redirect url.')->toArray()
        ];

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );
        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_not_successful_next_biller_is_third_party_and_redirect_url_exist(
    ): void
    {
        $memberId   = $this->mockUUIDForClass(MemberId::class);
        $purchaseId = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId  = $this->faker->uuid;

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();

        $cascade    = Cascade::create(
            BillerCollection::buildBillerCollection([new RocketgateBiller(), new EpochBiller()]),
            new RocketgateBiller(),
            2
        );
        $thirdParty = ThirdParty::create(route('thirdParty.redirect', ['jwt' => '']));

        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('state')->willReturn(Valid::create());

        $purchaseProcess->method('memberId')->willReturn((string) $memberId);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('purchaseId')->willReturn((string) $purchaseId);
        $purchaseProcess->method('cascade')->willReturn($cascade);
        $purchaseProcess->method('redirectUrl')->willReturn('https://redirect-url-to-client');

        $expected = [
            'success'    => false,
            'digest'     => '',
            'memberId'   => (string) $memberId,
            'purchaseId' => (string) $purchaseId,
            'sessionId' => (string) $sessionId,
            'nextAction' => RedirectToUrl::create($thirdParty)->toArray()
        ];

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @throws IllegalStateTransitionException
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_successful_with_cross_sales()
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $transactionId   = TransactionId::create();
        $site            = $this->createSite();

        $itemId    = $this->faker->uuid;
        $bundleId  = $this->faker->uuid;
        $addonId   = $this->faker->uuid;
        $sessionId = $this->faker->uuid;

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));
        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));

        if (config('app.feature.legacy_api_import')) {
            $purchaseProcess->method('isUsernamePadded')->willReturn(true);
        }


        $crossSale = $initializedItem;
        $crossSale->method('isCrossSale')->willReturn(true);

        $purchaseProcess->method('retrieveProcessedCrossSales')->willReturn([$crossSale]);

        $memberIdUUID = $this->faker->uuid;

        $purchaseIdUUID = $this->faker->uuid;
        $purchaseId     = PurchaseId::createFromString($purchaseIdUUID);

        $purchase = $this->createMock(Purchase::class);
        $purchase->method('purchaseId')->willReturn($purchaseId);

        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_APPROVED,
            RocketgateBiller::BILLER_NAME,
            null
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $subscriptionIdUUID = $this->faker->uuid;

        $processedBundleItem = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($subscriptionIdUUID),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        $processedItemsCollection = new ProcessedItemsCollection();
        $processedItemsCollection->offsetSet($itemId, $processedBundleItem);

        $purchase->method('items')->willReturn($processedItemsCollection);
        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);
        $purchaseProcess->method('state')->willReturn(Processed::create());

        $expected = [
            'success'          => true,
            'digest'           => '',
            'memberId'         => $memberIdUUID,
            'purchaseId'       => (string) $purchaseId,
            'bundleId'         => $bundleId,
            'addonId'          => $addonId,
            'subscriptionId'   => $subscriptionIdUUID,
            'itemId'           => $itemId,
            'transactionId'    => (string) $transactionId,
            'sessionId'        => $sessionId,
            'crossSells'       => [
                [
                    'success'        => true,
                    'bundleId'       => $bundleId,
                    'addonId'        => $addonId,
                    'subscriptionId' => $subscriptionIdUUID,
                    'transactionId'  => (string) $transactionId,
                    'itemId'         => $itemId,
                ]
            ],
            'nextAction'       => ['type' => 'finishProcess']
        ];

        if (config('app.feature.legacy_api_import')) {
            $expected['isUsernamePadded'] = true;
        }

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_existent_member_id_in_response_when_it_comes_from_an_unsuccessful_secondary_revenue_purchase(
    ): void
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $transactionId   = TransactionId::create();
        $site            = $this->createSite();

        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('state')->willReturn($this->createMock(Processed::class));

        $memberId = $this->faker->uuid;
        $purchaseProcess->method('memberId')->willReturn($memberId);

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($memberId, $processPurchaseGeneralHttpDTO->jsonSerialize()['memberId']);
        $this->assertArrayNotHasKey('transactionId', $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws InvalidStateException
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_pending_with_cross_sales()
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();

        $itemId        = $this->faker->uuid;
        $bundleId      = $this->faker->uuid;
        $addonId       = $this->faker->uuid;
        $sessionId     = $this->faker->uuid;
        $transactionId = $this->faker->uuid;

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));
        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemPurchasePending')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn(TransactionId::createFromString($transactionId));
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $crossSale = $initializedItem;
        $crossSale->method('isCrossSale')->willReturn(true);

        $purchaseProcess->method('retrieveProcessedCrossSales')->willReturn([$crossSale]);

        $memberIdUUID = $this->faker->uuid;

        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($transactionId),
            Transaction::STATUS_PENDING,
            RocketgateBiller::BILLER_NAME
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($addonId));

        $processedBundleItem = ProcessedBundleItem::create(
            null,
            ItemId::createFromString($itemId),
            $transactions,
            BundleId::createFromString($bundleId),
            $addons
        );

        $processedItemsCollection = new ProcessedItemsCollection();
        $processedItemsCollection->offsetSet(
            $itemId,
            $processedBundleItem
        );

        $purchaseProcess->method('purchase')->willReturn(null);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);
        $purchaseProcess->method('state')->willReturn(Pending::create());

        $nextAction = NextActionProcessFactory::create(
            $purchaseProcess->state(),
            route('threed.authenticate', ['jwt' => ''])
        )->toArray();

        $expected = [
            'success'          => true,
            'digest'           => '',
            'memberId'         => $memberIdUUID,
            'bundleId'         => $bundleId,
            'addonId'          => $addonId,
            'itemId'           => $itemId,
            'transactionId'    => (string) $transactionId,
            'sessionId'        => $sessionId,
            'crossSells'       => [
                [
                    'success'       => true,
                    'bundleId'      => $bundleId,
                    'addonId'       => $addonId,
                    'itemId'        => $itemId,
                    'transactionId' => (string) $transactionId
                ]
            ],
            'nextAction'       => $nextAction
        ];

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $actual                 = $processPurchaseGeneralHttpDTO->jsonSerialize();
        $expected['purchaseId'] = $actual['purchaseId'];
        $actual['sessionId']    = $sessionId;

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_redirect_to_fallback_processor_next_action_when_the_process_has_a_state_of_cascade_billers_exhausted(
    ): void
    {
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->onlyMethods(
                [
                    'state',
                    'retrieveMainPurchaseItem',
                    'memberId',
                    'publicKeyIndex',
                    'sessionId',
                    'isCurrentBillerAvailablePaymentsMethods'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $purchaseProcess->method('state')->willReturn(CascadeBillersExhausted::create());
        $purchaseProcess->method('publicKeyIndex')->willReturn(1);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::create());
        $purchaseProcess->method('isCurrentBillerAvailablePaymentsMethods')->willReturn(false);

        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();

        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $memberId = $this->faker->uuid;
        $purchaseProcess->method('memberId')->willReturn($memberId);

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $data = $processPurchaseGeneralHttpDTO->jsonSerialize();

        $this->assertSame(RedirectToFallbackProcessor::TYPE, $data['nextAction']['type']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_successful_with_cross_sale_with_pending_transaction(
    )
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $transactionId   = TransactionId::create();
        $site            = $this->createSite();

        $itemId   = $this->faker->uuid;
        $bundleId = $this->faker->uuid;
        $addonId  = $this->faker->uuid;
        $sessionId = $this->faker->uuid;

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));

        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);

        if (config('app.feature.legacy_api_import')) {
            $purchaseProcess->method('isUsernamePadded')->willReturn(true);
        }

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));

        $crossSale = $initializedItem;
        $crossSale->method('isCrossSale')->willReturn(true);
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('isPending')->willReturn(true);
        $crossSale->method('lastTransaction')->willReturn($transaction);

        $purchaseProcess->method('retrieveProcessedCrossSales')->willReturn([$crossSale]);

        $memberIdUUID = $this->faker->uuid;

        $purchaseIdUUID = $this->faker->uuid;
        $purchaseId     = PurchaseId::createFromString($purchaseIdUUID);

        $purchase = $this->createMock(Purchase::class);
        $purchase->method('purchaseId')->willReturn($purchaseId);

        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_APPROVED,
            RocketgateBiller::BILLER_NAME,
            null
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $subscriptionIdUUID = $this->faker->uuid;

        $processedBundleItem = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($subscriptionIdUUID),
                'test1234'
            ),
            ItemId::createFromString($this->faker->uuid),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        $processedItemsCollection = new ProcessedItemsCollection();
        $processedItemsCollection->offsetSet($itemId, $processedBundleItem);

        $purchase->method('items')->willReturn($processedItemsCollection);
        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);
        $purchaseProcess->method('state')->willReturn(Processed::create());

        $expected = [
            'success'          => true,
            'digest'           => '',
            'memberId'         => $memberIdUUID,
            'purchaseId'       => (string) $purchaseId,
            'bundleId'         => $bundleId,
            'addonId'          => $addonId,
            'subscriptionId'   => $subscriptionIdUUID,
            'itemId'           => $itemId,
            'transactionId'    => (string) $transactionId,
            'sessionId'        => $sessionId,
            'nextAction'       => ['type' => 'finishProcess']
        ];

        if (config('app.feature.legacy_api_import')) {
            $expected['isUsernamePadded'] = true;
        }

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_declined_and_nsf_enabled_on_site()
    {
        $memberId   = $this->mockUUIDForClass(MemberId::class);
        $purchaseId = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId  = $this->faker->uuid;

        $purchase = $this->createMock(Purchase::class);

        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_DECLINED,
            RocketgateBiller::BILLER_NAME,
            null,
            null,
            null,
            null,
            true
        );

        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $subscriptionIdUUID = $this->faker->uuid;
        $itemId             = $this->faker->uuid;

        $processedBundleItem = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($subscriptionIdUUID),
                'test1234'
            ),
            ItemId::createFromString($itemId),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        $processedItemsCollection = new ProcessedItemsCollection();
        $processedItemsCollection->offsetSet($itemId, $processedBundleItem);

        $purchase->method('items')->willReturn($processedItemsCollection);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite(false, true);
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemNsfPurchase')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('state')->willReturn(Valid::create());
        $purchaseProcess->method('state')->willReturn($this->createMock(Valid::class));
        $purchaseProcess->method('memberId')->willReturn((string) $memberId);
        $purchaseProcess->method('purchaseId')->willReturn((string) $purchaseId);
        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));

        $fraud = $this->createMock(FraudAdvice::class);
        $fraud->method('isForceThreeD')->willReturn(false);
        $purchaseProcess->method('fraudAdvice')->willReturn($fraud);

        $expected = [
            'success'        => false,
            'digest'         => '',
            'memberId'       => (string) $memberId,
            'purchaseId'     => (string) $purchaseId,
            'sessionId'      => (string) $sessionId,
            'nextAction'     => RenderGateway::create()->toArray()
        ];

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );


        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_main_purchase_was_nsf_declined_and_nsf_enabled_on_site()
    {
        $memberId   = $this->mockUUIDForClass(MemberId::class);
        $purchaseId = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId  = $this->faker->uuid;

        $purchase = $this->createMock(Purchase::class);

        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_DECLINED,
            RocketgateBiller::BILLER_NAME,
            null,
            null,
            null,
            null,
            true
        );
        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $subscriptionIdUUID = $this->faker->uuid;
        $itemId             = $this->faker->uuid;

        $processedBundleItem = ProcessedBundleItem::create(
            SubscriptionInfo::create(
                SubscriptionId::createFromString($subscriptionIdUUID),
                'test1234'
            ),
            ItemId::createFromString($itemId),
            $transactions,
            BundleId::createFromString($this->faker->uuid),
            $addons
        );

        $processedItemsCollection = new ProcessedItemsCollection();
        $processedItemsCollection->offsetSet($itemId, $processedBundleItem);

        $purchase->method('items')->willReturn($processedItemsCollection);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite(false, true);
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $initializedItem->method('wasItemNsfPurchase')->willReturn(true);
        $purchaseProcess->method('state')->willReturn(Valid::create());
        $purchaseProcess->method('state')->willReturn($this->createMock(Valid::class));
        $purchaseProcess->method('memberId')->willReturn((string) $memberId);
        $purchaseProcess->method('purchaseId')->willReturn((string) $purchaseId);
        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $initializedItem->method('subscriptionId')->willReturn($subscriptionIdUUID);

        $fraud = $this->createMock(FraudAdvice::class);
        $fraud->method('isForceThreeD')->willReturn(false);
        $purchaseProcess->method('fraudAdvice')->willReturn($fraud);

        $expected = [
            'success'        => false,
            'digest'         => '',
            'memberId'       => (string) $memberId,
            'purchaseId'     => (string) $purchaseId,
            'subscriptionId' => $subscriptionIdUUID,
            'sessionId'      => $sessionId,
            'nextAction'     => RenderGateway::create()->toArray(),
            'isNsf'          => true
        ];

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }
    
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_3ds_authenticate_when_pending_rocketgate_transaction():void
    {
        //GIVEN
        $site            = $this->createSite();
        $purchaseProcess = $this->purchaseProcessWithPendingState($site->id());

        //WHEN
        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            RocketgateBiller::BILLER_NAME
        );
        $transactions->add($transaction);

        $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        //THEN
        $result = $processPurchaseGeneralHttpDTO->jsonSerialize();

        $this->assertEquals('authenticate3D',$result["nextAction"]['type']);
        $this->assertArrayHasKey('authenticateUrl', $result["nextAction"]['threeD']);
    }

    /**
     * @param string $siteId
     *
     * @return PurchaseProcess
     * @throws IllegalStateTransitionException*@throws \Exception
     * @throws \Exception
     */
    private function purchaseProcessWithPendingState(string $siteId): PurchaseProcess
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $transactionId   = TransactionId::create();

        $itemId       = $this->faker->uuid;
        $bundleId     = $this->faker->uuid;
        $addonId      = $this->faker->uuid;
        $sessionId    = $this->faker->uuid;
        $memberIdUUID = $this->faker->uuid;

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn(SiteId::createFromString($siteId));
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));
        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemPurchasePending')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));

        $purchaseProcess->method('purchase')->willReturn(null);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);
        $purchaseProcess->method('state')->willReturn(Pending::create());

        return $purchaseProcess;
    }
}
