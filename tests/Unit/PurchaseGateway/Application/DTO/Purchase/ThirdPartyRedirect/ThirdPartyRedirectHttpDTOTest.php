<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\ThirdPartyRedirect;

use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedItemsCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SubscriptionInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Tests\UnitTestCase;

class ThirdPartyRedirectHttpDTOTest extends UnitTestCase
{
    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /** @var CryptService */
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
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_a_successful_redirect_is_done()
    {
        $redirectUrl     = $this->faker->url;
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();

        $itemId   = $this->faker->uuid;
        $bundleId = $this->faker->uuid;
        $addonId  = $this->faker->uuid;

        $transactions = new TransactionCollection();
        $transaction  = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            'epoch',
            true,
            null,
            null,
            $redirectUrl
        );

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

        $initializedItem->method('wasItemPurchasePending')->willReturn(true);
        $initializedItem->method('wasItemPurchaseSuccessfulOrPending')->willReturn(true);
        $initializedItem->method('lastTransaction')->willReturn($transaction);

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('redirectUrl')->willReturn($redirectUrl);

        $crossSale = $initializedItem;
        $crossSale->method('isCrossSale')->willReturn(true);

        $purchaseProcess->method('retrieveProcessedCrossSales')->willReturn([$crossSale]);

        $memberIdUUID = $this->faker->uuid;

        $purchaseIdUUID = $this->faker->uuid;
        $purchaseId     = PurchaseId::createFromString($purchaseIdUUID);

        $purchase = $this->createMock(Purchase::class);
        $purchase->method('purchaseId')->willReturn($purchaseId);

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
        $purchaseProcess->method('state')->willReturn(Pending::create());

        $expected = [
            'redirectUrl' => $redirectUrl
        ];

        $redirectDTO = new ThirdPartyRedirectHttpDTO($purchaseProcess, $this->tokenGenerator, $this->cryptService,
            $site);
        $this->assertEquals($expected, $redirectDTO->jsonSerialize());
    }
}
