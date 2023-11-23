<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\Mgpg;

use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\InitPurchaseNgToMgpgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\InitPurchaseRequest;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\UnitTestCase;

class InitPurchaseNgToMgpgServiceTest extends UnitTestCase
{
    /**
     * @var MockObject|InitPurchaseRequest
     */
    private $mockedInitRequest;

    /**
     * @var MockObject|NgResponseService
     */
    private $mockedNgResponseService;

    /**
     * @var MockObject|TokenGenerator
     */
    private $mockedTokenGenerator;

    /**
     * @var MockObject|CryptService
     */
    private $mockedCryptService;

    /**
     * @var MockObject|ConfigService
     */
    private $mockedConfigService;

    protected function setUp(): void
    {
        $this->mockedInitRequest       = $this->createMock(InitPurchaseRequest::class);
        $this->mockedNgResponseService = $this->createMock(NgResponseService::class);
        $this->mockedTokenGenerator    = $this->createMock(TokenGenerator::class);
        $this->mockedCryptService      = $this->createMock(CryptService::class);
        $this->mockedConfigService     = $this->createMock(ConfigService::class);
        parent::setUp();
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_dws_city_and_postal_code_from_request(): void
    {
        $city        = 'Salvador';
        $postalCode  = '4500000';
        $asn         = "12345";
        $countryCode = 'US';

        $this->mockedInitRequest->expects($this->any())->method('getClientCountryCode')->willReturn('BR');

        $this->mockedInitRequest->expects($this->any())->method('getDws')->willReturn(
            [
                'maxMind' => [
                    'x-geo-city'         => $city,
                    'x-geo-postal-code'  => $postalCode,
                    'x-geo-asn'          => $asn,
                    'x-geo-country-code' => $countryCode
                ]
            ]
        );

        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->mockedInitRequest,
            $this->mockedNgResponseService,
            $this->mockedTokenGenerator,
            $this->mockedCryptService,
            $this->mockedConfigService
        );

        $method = $this->exposePrivateMethod(InitPurchaseNgToMgpgService::class, 'createDws');
        $dws    = $method->invokeArgs($initPurchaseNgToMgpgService, []);

        $xGeoCity        = $dws->toArray()['maxMind']['x-geo-city'];
        $xGeoPostalCode  = $dws->toArray()['maxMind']['x-geo-postal-code'];
        $xGeoCountryCode = $dws->toArray()['maxMind']['x-geo-country-code'];
        $xGeoAsn         = $dws->toArray()['maxMind']['x-geo-asn'];

        $this->assertEquals($city, $xGeoCity);
        $this->assertEquals($postalCode, $xGeoPostalCode);
        $this->assertEquals($countryCode, $xGeoCountryCode);
        $this->assertEquals($asn, $xGeoAsn);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_empty_dws_city_and_postal_code_when_they_are_empty_in_request(): void
    {
        $countryCodeOutDws = 'BR';
        $this->mockedInitRequest->expects($this->any())
            ->method('getClientCountryCode')
            ->willReturn($countryCodeOutDws);

        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->mockedInitRequest,
            $this->mockedNgResponseService,
            $this->mockedTokenGenerator,
            $this->mockedCryptService,
            $this->mockedConfigService
        );

        $method = $this->exposePrivateMethod(InitPurchaseNgToMgpgService::class, 'createDws');
        $dws    = $method->invokeArgs($initPurchaseNgToMgpgService, []);

        $xGeoCity        = $dws->toArray()['maxMind']['x-geo-city'];
        $xGeoPostalCode  = $dws->toArray()['maxMind']['x-geo-postal-code'];
        $xGeoCountryCode = $dws->toArray()['maxMind']['x-geo-country-code'];
        $xGeoDefaultAsn  = $dws->toArray()['maxMind']['x-geo-asn'];

        $this->assertEmpty($xGeoCity);
        $this->assertEmpty($xGeoPostalCode);
        $this->assertEquals($countryCodeOutDws, $xGeoCountryCode);
        $this->assertEquals(InitPurchaseNgToMgpgService::DEFAULT_ASN, $xGeoDefaultAsn);
    }

    /**
     * @var string
     */
    private $ngPayload = '{
        "usingMemberProfile": false,
        "siteId": "a2d4f06f-afc8-41c9-9910-0302bd2d9531",
        "bundleId": "5fd44440-2956-11e9-b210-d663bd873d93",
        "addonId": "670af402-2956-11e9-b210-d663bd873d93",
        "memberId": "86d84284-b080-4d5d-9d8c-1b7428fa495a",
        "itemId": "386c5ce2-ae88-49d2-b952-b32e052c3a88",
        "amount": 10,
        "initialDays": 7,
        "rebillAmount": 50,
        "rebillDays": 30,
        "addRemainingDays": false,
        "currency": "USD",
        "clientIp": "192.168.1.1",
        "clientCountryCode": "US",
        "atlasCode": "NDU1MDk1OjQ4OjE0Nw",
        "atlasData": "atlas data example",
        "tax": {
            "initialAmount": {
                "beforeTaxes": 7.0,
                "taxes": 0.5,
                "afterTaxes": 10
            },
            "rebillAmount": {
                "beforeTaxes": 29.99,
                "taxes": 0.5,
                "afterTaxes": 50
            },
            "taxApplicationId": "60bf5bcb-ac64-496c-acc5-9c7cf54a1869",
            "taxName": "Vat Tax",
            "taxRate": 0.05,
            "taxType": "vat"
        },
        "legacyMapping": {
            "legacyProductId": 33503,
            "legacyMemberId": 3410
        },
        "businessTransactionOperation": "subscriptionUpgrade",
        "paymentType": "banktransfer",
        "paymentMethod": "sepadirectdebit",
        "trafficSource": "ALL",
        "redirectUrl": "https://client-complete-return-url",
        "postbackUrl": "https://us-central1-mg-probiller-dev.cloudfunctions.net/postback-catchall",
        "overrides": {
            "cascade": {
                "callCascades": {
                    "billers": [
                        "centrobill"
                    ]
                }
            }
        }
    }';

    /**
     * @return array
     */
    public function provider_for_UsingMemberProfile_test(): array
    {
        return [
            'using TRUE'  => [true],
            'using FALSE' => [false],
        ];
    }

    /**
     * @test
     * @dataProvider provider_for_UsingMemberProfile_test
     * @throws \Exception
     */
    public function it_should_return_same_value_from_payload_usingMemberProfile($expectedUsingMemberProfile): void
    {
        $correlationId  = Uuid::uuid4()->toString();
        $ngPayloadArray = json_decode($this->ngPayload, true);
        $this->mockedInitRequest->expects($this->any())->method('toArray')->willReturn($ngPayloadArray);
        $this->mockedInitRequest->expects($this->any())->method('getUsingMemberProfile')->willReturn(
            $expectedUsingMemberProfile
        );
        
        $this->mockedInitRequest->attributes = new ParameterBag([
            'site'          => $this->createMock(Site::class),
            'sessionId'     => Uuid::uuid4()->toString(),
            'publicKeyId'   => 0,
            'correlationId' => $correlationId
        ]);
        $this->mockedInitRequest->expects($this->any())->method('input')
            ->withAnyParameters()
            ->willReturn(Uuid::uuid4()->toString());

        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->mockedInitRequest,
            $this->mockedNgResponseService,
            $this->mockedTokenGenerator,
            $this->mockedCryptService,
            $this->mockedConfigService
        );

        $method         = $this->exposePrivateMethod(InitPurchaseNgToMgpgService::class, 'createPurchaseInvoice');
        $createdInvoice = $method->invokeArgs($initPurchaseNgToMgpgService, [$correlationId]);

        $usingMemberProfileFromInvoice = $this->exposePrivateAttribute(
            get_class($createdInvoice),
            'usingMemberProfile'
        );
        $this->assertEquals($expectedUsingMemberProfile, $usingMemberProfileFromInvoice->getValue($createdInvoice));
    }
    
    /**
     * @param $class
     * @param $method
     *
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public function exposePrivateMethod($class, $method)
    {
        $class  = new ReflectionClass($class);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @return array[]
     */
    public function entitlementsProvider(): array
    {
        $basicPayload = array (
            'usingMemberProfile' => false,
            "siteId" => "86c29282-1304-4c74-a531-78b81bd8e7ca",
            "bundleId" => "33b144df-b192-4b3c-aae4-dd738d13efbe",
            "addonId" => "03cc6652-cb39-4261-b86d-1b9580078172",
            'itemId' => '386c5ce2-ae88-49d2-b952-b32e052c3a88',
            'currency' => 'USD',
            'clientIp' => '10.10.109.185',
            'paymentType' => 'cc',
            'clientCountryCode' => 'CA',
            'amount' => 1,
            'initialDays' => 2,
            'rebillDays' => 30,
            'rebillAmount' => 39.95,
        );
        $crossSale = $basicPayload + [ 'crossSellOptions' => [
                [
                    "siteId" => "fae20778-3d01-41d2-a8a7-4e5e46ea864a",
                    "bundleId" => "35f48e36-b29a-4fcd-ba88-e7d9a2a83bfd",
                    "addonId" => "bb699f94-5351-401e-af3e-ed1094bdc687",
                    'subscriptionId' => '87266bdd-96e9-47cd-8314-eaa4f9494846',
                    'initialDays' => 2,
                    'rebillDays' => 30,
                    'amount' => 187.6,
                    'legacyProductId' => 123,
                    'rebillAmount' => 187.6,
                    'isTrial' => false,
                    'tax' =>
                        array (
                            'initialAmount' =>
                                array (
                                    'beforeTaxes' => 160.8,
                                    'taxes' => 26.8,
                                    'afterTaxes' => 187.6,
                                ),
                            'rebillAmount' =>
                                array (
                                    'beforeTaxes' => 160.8,
                                    'taxes' => 26.8,
                                    'afterTaxes' => 187.6,
                                ),
                            'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                            'taxName' => 'Tax Name',
                            'taxRate' => 0.05,
                            'taxType' => 'vat',
                        ),
                    'entitlements' => [
                        array (
                            'any' =>
                                array (
                                    'data' => 'TESTING',
                                ),
                            'isTrial' => true,
                        ),
                    ],
                ]
            ]
        ];
        return [
            'valid entitlement' => [$basicPayload+['entitlements' => ['any' => ['data' => 'TEST'], 'isTrial' => true]], true],
            'empty entitlement' => [$basicPayload, false],
            'cross sale entitlement' => [$crossSale, true],
        ];
    }

    /**
     * @test
     * @dataProvider entitlementsProvider
     * @throws \ReflectionException
     */
    public function it_should_transfer_entitlements_from_client_to_MGPG($payload, $expectSame)
    {
        $site = $this->createMock(Site::class);
        $site->method('name')->willReturn('test');
        $site->method('descriptor')->willReturn('test');
        $site->method('supportLink')->willReturn('test');
        $site->method('phoneNumber')->willReturn('test');
        $site->method('mailSupportLink')->willReturn('test');
        $site->method('messageSupportLink')->willReturn('test');
        $site->method('skypeNumber')->willReturn('test');
        $site->method('url')->willReturn('test');
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);

        $this->mockedConfigService->expects($this->any())->method('getSite')
            ->withAnyParameters()
            ->willReturn($site);
        
        $this->mockedInitRequest->attributes = new ParameterBag([
            'site'          => $site,
            'sessionId'     => Uuid::uuid4()->toString(),
            'publicKeyId'   => 0,
            'correlationId' => Uuid::uuid4()->toString()
        ]);
        $this->mockedInitRequest->expects($this->any())->method('input')
            ->withAnyParameters()
            ->willReturn(Uuid::uuid4()->toString());

        $json = new ParameterBag($payload);
        $this->mockedInitRequest->expects($this->any())->method('json')->willReturn($json);
        $this->mockedInitRequest->expects($this->any())->method('toArray')->willReturn($payload);
        
        if(isset($payload['crossSellOptions'])) {
            $this->mockedInitRequest->expects($this->any())->method('getCrossSales')->willReturn($payload['crossSellOptions']);
            $originalEntitlement = $payload['crossSellOptions'][0]['entitlements'];
        } else {
            $originalEntitlement = $payload['entitlements'] ?? [];
            $this->mockedInitRequest->expects($this->any())->method('getEntitlementsFromClient')->willReturn($originalEntitlement);
        }
        
        $initPurchaseNgToMgpgService = new InitPurchaseNgToMgpgService(
            $this->mockedInitRequest,
            $this->mockedNgResponseService,
            $this->mockedTokenGenerator,
            $this->mockedCryptService,
            $this->mockedConfigService
        );
        
        $method = $this->exposePrivateMethod(InitPurchaseNgToMgpgService::class, 'createCharges');
        $charges = $method->invokeArgs($initPurchaseNgToMgpgService, []);
        
        $output = $charges[0]->items[0];
        if(isset($payload['crossSellOptions'])) {
            $output = $charges[1]->items[0];
        }
        
        $attr = $this->exposePrivateAttribute(get_class($output), 'entitlements');
        $outputEntitlement = $attr->getValue($output);
        
        if ($expectSame) {
            $this->assertArrayHasKey('extended', $outputEntitlement[0]);
            $this->assertEquals($originalEntitlement, $outputEntitlement[0]['extended']);//Looking for the EXTENDED position
        } else {
            $this->assertArrayNotHasKey('extended', $outputEntitlement[0]);
        }
    }

    /**
     * @param $className
     * @param $attrName
     *
     * @return \ReflectionProperty
     * @throws \ReflectionException
     */
    public function exposePrivateAttribute($className, $attrName)
    {
        $reflector = new ReflectionClass($className);
        $property  = $reflector->getProperty($attrName);
        $property->setAccessible(true);

        return $property;
    }
}
