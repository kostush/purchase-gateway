<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\Lookup;

use ProBillerNG\PurchaseGateway\Application\DTO\Lookup\LookupThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionProcessFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDLookupPerformed;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Tests\UnitTestCase;

class LookupThreeDHttpDTOTest extends UnitTestCase
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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_threed_lookup_purchase_was_not_successful_with_frictionless(
    ): void
    {
        $memberId      = $this->mockUUIDForClass(MemberId::class);
        $purchaseId    = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId     = $this->faker->uuid;
        $transactionId = TransactionId::create();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $redirectUrl     = $this->faker->url;

        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('redirectUrl')->willReturn($redirectUrl);
        $purchaseProcess->method('state')->willReturn(Valid::create());
        $purchaseProcess->method('memberId')->willReturn((string) $memberId);
        $purchaseProcess->method('purchaseId')->willReturn((string) $purchaseId);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('isCurrentBillerAvailablePaymentsMethods')->willReturn(false);
        $purchaseProcess->method('isCurrentBillerAvailablePaymentsMethods')->willReturn(false);

        $expected = [
            'success'          => false,
            'digest'           => '',
            'memberId'         => (string) $memberId,
            'purchaseId'       => (string) $purchaseId,
            'sessionId'        => (string) $sessionId,
            'nextAction'       => ['type' => 'renderGateway']
        ];

        $processPurchaseGeneralHttpDTO = new LookupThreeDHttpDTO(
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
    public function it_should_return_the_correct_dto_structure_when_a_successful_frictionless_is_done(): void
    {
        $redirectUrl     = $this->faker->url;
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $transactionId   = TransactionId::create();
        $site            = $this->createSite();

        $itemId   = $this->faker->uuid;
        $bundleId = $this->faker->uuid;
        $addonId  = $this->faker->uuid;
        $sessionId= $this->faker->uuid;

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));

        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('redirectUrl')->willReturn($redirectUrl);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('isCurrentBillerAvailablePaymentsMethods')->willReturn(false);

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
            'approved',
            'rocketgate'
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
        $processedItemsCollection->offsetSet(
            $itemId,
            $processedBundleItem
        );

        $purchase->method('items')->willReturn($processedItemsCollection);
        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);
        $purchaseProcess->method('state')->willReturn(Processed::create());

        $expected = [
            'success'          => true,
            'digest'           => '',
            'memberId'         => (string) $memberIdUUID,
            'purchaseId'       => (string) $purchaseId->value(),
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
                    'itemId'         => $itemId,
                    'transactionId'  => (string) $transactionId
                ]
            ],
            'nextAction'       => ['type' => 'finishProcess']
        ];

        if (config('app.feature.legacy_api_import')) {
            $expected['isUsernamePadded'] = false;
        }

        $processPurchaseGeneralHttpDTO = new LookupThreeDHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_threed_lookup_purchase_retrieve_3ds_version_2(
    ): void
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $purchaseProcess->method('state')->willReturn(ThreeDLookupPerformed::create());
        $transactionId = TransactionId::create();
        $site          = $this->createSite();

        $itemId   = $this->faker->uuid;
        $bundleId = $this->faker->uuid;
        $addonId  = $this->faker->uuid;
        $sessionId= $this->faker->uuid;

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'pending',
            'rocketgate',
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $transaction->setThreeDVersion(2);
        $transaction->setThreeDStepUpJwt('jwt');
        $transaction->setThreeDStepUpUrl('url');
        $transaction->setMd('md');

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));
        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);
        $initializedItem->method('lastTransaction')->willReturn($transaction);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('retrieveProcessedCrossSales')->willReturn([]);

        $memberIdUUID = $this->faker->uuid;

        $purchaseIdUUID = $this->faker->uuid;
        $purchaseProcess->method('purchaseId')->willReturn($purchaseIdUUID);

        $transactions = new TransactionCollection();

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
        $processedItemsCollection->offsetSet(
            $itemId,
            $processedBundleItem
        );

        //$purchase->method('items')->willReturn($processedItemsCollection);
        //  $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);

        $expected = [
            'success'          => true,
            'digest'           => '',
            'memberId'         => (string) $memberIdUUID,
            'bundleId'         => $bundleId,
            'addonId'          => $addonId,
            'itemId'           => $itemId,
            'transactionId'    => (string) $transactionId,
            'sessionId'        => $sessionId,
            'nextAction'       => [
                'type'    => 'authenticate3D',
                "version" => 2,
                "threeD"  => [
                    "authenticateUrl" => "url",
                    "jwt"             => "jwt",
                    "md"              => "md"
                ]
            ],
            'purchaseId'       => $purchaseIdUUID
        ];

        $processPurchaseGeneralHttpDTO = new LookupThreeDHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_threed_lookup_purchase_retrieve_3ds_version_1(
    ): void
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $purchaseProcess->method('state')->willReturn(ThreeDLookupPerformed::create());
        $transactionId = TransactionId::create();
        $site          = $this->createSite();

        $itemId   = $this->faker->uuid;
        $bundleId = $this->faker->uuid;
        $addonId  = $this->faker->uuid;
        $sessionId = $this->faker->uuid;

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'pending',
            'rocketgate',
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $transaction->setThreeDVersion(1);
        $transaction->setAcs('acs');
        $transaction->setPareq('pareq');

        $initializedItem->method('itemId')->willReturn(ItemId::createFromString($itemId));
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $initializedItem->method('bundleId')->willReturn(BundleId::createFromString($bundleId));
        $initializedItem->method('addonId')->willReturn(AddonId::createFromString($addonId));
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransactionId')->willReturn($transactionId);
        $initializedItem->method('lastTransaction')->willReturn($transaction);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));
        $purchaseProcess->method('retrieveProcessedCrossSales')->willReturn([]);

        $memberIdUUID = $this->faker->uuid;

        $purchaseIdUUID = $this->faker->uuid;
        $purchaseProcess->method('purchaseId')->willReturn($purchaseIdUUID);

        $transactions = new TransactionCollection();

        $transactions->add($transaction);

        $addons = new AddonCollection();
        $addons->add(AddonId::createFromString($this->faker->uuid));

        $subscriptionIdUUID = $this->faker->uuid;

        $nextAction = NextActionProcessFactory::create(
            $purchaseProcess->state(),
            route('threed.authenticate', ['jwt' => ''])
        )->toArray();

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
        $processedItemsCollection->offsetSet(
            $itemId,
            $processedBundleItem
        );

        //$purchase->method('items')->willReturn($processedItemsCollection);
        //  $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('memberId')->willReturn($memberIdUUID);

        $expected = [
            'success'          => true,
            'digest'           => '',
            'memberId'         => (string) $memberIdUUID,
            'bundleId'         => $bundleId,
            'addonId'          => $addonId,
            'itemId'           => $itemId,
            'transactionId'    => (string) $transactionId,
            'sessionId'        => $sessionId,
            'nextAction'       => $nextAction,
            'purchaseId'       => $purchaseIdUUID
        ];

        $processPurchaseGeneralHttpDTO = new LookupThreeDHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $processPurchaseGeneralHttpDTO->jsonSerialize());
    }
}
