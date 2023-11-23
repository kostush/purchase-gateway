<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\ThirdPartyReturn;

use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Tests\UnitTestCase;

class ReturnHttpDTOTest extends UnitTestCase
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
     * @throws \Exception
     * @return void
     */
    public function it_should_return_the_correct_dto_structure_when_return_purchase_was_not_successful(): void
    {
        $memberId   = $this->mockUUIDForClass(MemberId::class);
        $purchaseId = $this->mockUUIDForClass(PurchaseId::class);
        $sessionId  = $this->faker->uuid;

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

        $expected = [
            'success'     => false,
            'digest'      => '',
            'memberId'    => (string) $memberId,
            'purchaseId'  => (string) $purchaseId,
            'redirectUrl' => $redirectUrl,
            'sessionId'   => (string) $sessionId,
            'nextAction'  => ['type' => 'renderGateway']
        ];

        $dto = new ReturnHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $dto->jsonSerialize());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_a_successful_return_is_done(): void
    {
        $redirectUrl     = $this->faker->url;
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();

        $itemId   = $this->faker->uuid;
        $bundleId = $this->faker->uuid;
        $addonId  = $this->faker->uuid;
        $sessionId = $this->faker->uuid;

        $initializedItem->method('itemId')->willReturn(
            ItemId::createFromString($itemId)
        );

        $initializedItem->method('siteId')->willReturn($site->siteId());

        $initializedItem->method('bundleId')->willReturn(
            BundleId::createFromString($bundleId)
        );

        $initializedItem->method('addonId')->willReturn(
            AddonId::createFromString($addonId)
        );

        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('redirectUrl')->willReturn($redirectUrl);
        $purchaseProcess->method('sessionId')->willReturn(SessionId::createFromString($sessionId));

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
            'success'        => true,
            'digest'         => '',
            'memberId'       => (string) $memberIdUUID,
            'purchaseId'     => $purchaseId->value(),
            'bundleId'       => $bundleId,
            'addonId'        => $addonId,
            'subscriptionId' => $subscriptionIdUUID,
            'itemId'         => $itemId,
            'sessionId'      => $sessionId,
            'crossSells'     => [
                [
                    'success'        => true,
                    'bundleId'       => $bundleId,
                    'addonId'        => $addonId,
                    'subscriptionId' => $subscriptionIdUUID,
                    'itemId'         => $itemId,
                ]
            ],
            'nextAction'     => ['type' => 'finishProcess'],
            'redirectUrl'    => $redirectUrl
        ];

        if (config('app.feature.legacy_api_import')) {
            $expected['isUsernamePadded'] = false;
        }

        $dto = new ReturnHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );

        $this->assertEquals($expected, $dto->jsonSerialize());
    }
}
