<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Domain\Services\Mgpg;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\InitPurchaseNgToMgpgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\InitPurchaseRequest;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\IntegrationTestCase;

class InitPurchaseNgToMgpgServiceTest extends IntegrationTestCase
{
    private $initRequest;

    private $ngResponseService;

    private $tokenGenerator;

    private $cryptService;

    private $configService;

    private $ngPayload = '{
            "siteId": "8e34c94e-135f-4acb-9141-58b3a6e56c74",
            "bundleId": "4475820e-2956-11e9-b210-d663bd873d93",
            "addonId": "4e1b0d7e-2956-11e9-b210-d663bd873d93",
            "currency": "USD",
            "clientIp": "10.10.109.185",
            "paymentType": "cc",
            "paymentMethod": "visa",
            "clientCountryCode": "US",
            "amount": 0.01,
            "initialDays": 5,
            "atlasCode": "NDU1MDk1OjQ4OjE0Nw",
            "atlasData": "atlas data example",
            "isTrial": false,
            "postbackUrl": "http:\/\/example.com\/",
            "redirectUrl": "http:\/\/www.example.com",
            "crossSellOptions": [

            ]
        }';


    protected function setUp(): void
    {
        $site = $this->createMock(Site::class);
        $businessGroupId = BusinessGroupId::createFromString("07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1");

        $site->method('businessGroupId')->willReturn($businessGroupId);

        $parameterBag = new ParameterBag([
            'site'          => $site,
            'sessionId'     => Uuid::uuid4()->toString(),
            'publicKeyId'   => 0,
            'correlationId' => Uuid::uuid4()->toString()
        ]);

        $this->initRequest = $this->createMock(InitPurchaseRequest::class);
        $this->initRequest->attributes = $parameterBag;
        $this->initRequest->expects($this->any())->method('input')->withAnyParameters()->willReturn(
            Uuid::uuid4()->toString()
        );
        $this->initRequest->expects($this->once())->method('getCrossSales')->willReturn([]);

        $this->ngResponseService = new NgResponseService();
        $this->tokenGenerator = $this->createMock(TokenGenerator::class);
        $this->cryptService = $this->createMock(CryptService::class);
        $this->configService = $this->createMock(ConfigService::class);

        parent::setUp();
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_translate_into_singleChargePurchase()
    {
        $ngPayloadArray = json_decode($this->ngPayload, true);
        $ngPayloadArray['initialDays'] = 0;
        $this->initRequest->expects($this->any())->method('toArray')->willReturn($ngPayloadArray);

        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->initRequest,
            $this->ngResponseService,
            $this->tokenGenerator,
            $this->cryptService,
            $this->configService
        );

        $mgpgRequest = $initPurchaseNgToMgpgService->translate(Uuid::uuid4()->toString());

        $this->assertEquals('singleChargePurchase', $mgpgRequest->toArray()['invoice']['charges'][0]['businessTransactionOperation']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_translate_into_subscriptionPurchase_with_no_rebill()
    {
        $ngPayloadArray = json_decode($this->ngPayload, true);
        $this->initRequest->expects($this->any())->method('toArray')->willReturn($ngPayloadArray);

        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->initRequest,
            $this->ngResponseService,
            $this->tokenGenerator,
            $this->cryptService,
            $this->configService
        );

        $mgpgRequest = $initPurchaseNgToMgpgService->translate(Uuid::uuid4()->toString());

        $this->assertEquals(
            'subscriptionPurchase',
            $mgpgRequest->toArray()['invoice']['charges'][0]['businessTransactionOperation']
        );

        $purchaseItem = array_keys($mgpgRequest->toArray()['invoice']['charges'][0]['items'][0]);

        // Here we are checking if purchase item does not contain any rebill information
        foreach ($purchaseItem as $keyName => $keyValue) {
            if (!strpos('rebill', strtolower($keyValue))) {
                unset($purchaseItem[$keyName]);
            }
        }

        $this->assertTrue(empty($purchaseItem));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_translate_into_subscriptionPurchase()
    {
        $ngPayloadArray = json_decode($this->ngPayload, true);
        $ngPayloadArray['initialDays'] = 5;
        $ngPayloadArray['rebillDays'] = 5;
        $ngPayloadArray['rebillAmount'] = 25;

        $this->initRequest->expects($this->any())->method('toArray')->willReturn($ngPayloadArray);

        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->initRequest,
            $this->ngResponseService,
            $this->tokenGenerator,
            $this->cryptService,
            $this->configService
        );

        $mgpgRequest = $initPurchaseNgToMgpgService->translate(Uuid::uuid4()->toString());

        $this->assertEquals(
            'subscriptionPurchase',
            $mgpgRequest->toArray()['invoice']['charges'][0]['businessTransactionOperation']
        );
        $this->assertTrue(array_key_exists('rebill', $mgpgRequest->toArray()['invoice']['charges'][0]['items'][0]));
    }
}