<?php

namespace PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use Google\Protobuf\Timestamp;
use Grpc\UnaryCall;
use Probiller\Common\BillerMapping as CommonBillerMapping;
use Probiller\Common\Fields\BillerData;
use Probiller\Common\Fields\BillerFields;
use Probiller\Rocketgate\RocketgateFields;
use Probiller\Service\Config\ProbillerConfigClient;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping\BillerMappingTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use stdClass;
use Tests\IntegrationTestCase;

class BillerMappingTranslatingServiceTest extends IntegrationTestCase
{
    /**
     * @test
     *
     * @throws Exception
     * @throws BillerMappingException
     * @throws UnknownBillerNameException
     * @return void
     */
    public function it_should_return_biller_mapping_translated_when_got_a_valid_response_from_config_service(): void
    {
        $rocketgate = new RocketgateFields(
            [
                'merchantId'       => 'test-id',
                'merchantPassword' => 'test-pass',
                'merchantSiteId'   => '12341234',
                'sharedSecret'     => 'sharedSecret',
                'simplified3DS'    => true
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'rocketgate',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['rocketgate' => $rocketgate])
            ]
        );

        $billerMappingResponse = new CommonBillerMapping(
            [
                'billerMappingId'     => $this->faker->uuid,
                'businessGroupId'     => $this->faker->uuid,
                'active'              => true,
                'siteId'              => $this->faker->uuid,
                'createdAt'           => new Timestamp(),
                'updatedAt'           => new Timestamp(),
                'availableCurrencies' => [
                    'USD',
                    'CAD'
                ],
                'biller'              => $biller,
            ]
        );

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new stdClass();
        $status->code = 0;

        $unaryCallMock->method('wait')->willReturn(
            [
                $billerMappingResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetBillerMappingConfigFiltered')->willReturn(
            $unaryCallMock
        );

        $configService = new ConfigService($probillerConfigClientMock);

        $billerMappingService = new BillerMappingTranslatingService($configService);

        $billerMappingResult = $billerMappingService->retrieveBillerMapping(
            'rocketgate',
            $this->faker->uuid,
            $this->faker->uuid,
            'CAD',
            $this->faker->uuid
        );

        $this->assertInstanceOf(BillerMapping::class, $billerMappingResult);
        $this->assertEquals('rocketgate', $billerMappingResult->billerName());
        $this->assertEqualsCanonicalizing(
            [
                'merchantId'         => 'test-id',
                'merchantPassword'   => 'test-pass',
                'siteId'             => '12341234',
                'sharedSecret'       => 'sharedSecret',
                'simplified3DS'      => true,
                'merchantCustomerId' => null,
                'merchantInvoiceId'  => null
            ],
            $billerMappingResult->billerFields()->toArray()
        );
    }

    /**
     * @test
     *
     * @throws Exception
     * @throws BillerMappingException
     * @throws UnknownBillerNameException
     * @return void
     */
    public function it_should_throws_exception_when_got_invalid_response_from_config_service(): void
    {
        $this->expectException(BillerMappingException::class);
        $rocketgate = new RocketgateFields(
            [
                'merchantId'       => 'test-id',
                'merchantPassword' => 'test-pass',
                'merchantSiteId'   => '12341234'
            ]
        );

        $biller = new BillerData(
            [
                'name'         => 'rocketgate',
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['rocketgate' => $rocketgate])
            ]
        );

        /**
         * $availableCurrencies is an empty list, it will make throws a exception
         */
        $billerMappingResponse = new CommonBillerMapping(
            [
                'billerMappingId'     => $this->faker->uuid,
                'businessGroupId'     => $this->faker->uuid,
                'active'              => true,
                'siteId'              => $this->faker->uuid,
                'createdAt'           => new Timestamp(),
                'updatedAt'           => new Timestamp(),
                'availableCurrencies' => [
                ],
                'biller'              => $biller,
            ]
        );

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new stdClass();
        $status->code = 0;

        $unaryCallMock->method('wait')->willReturn(
            [
                $billerMappingResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetBillerMappingConfigFiltered')->willReturn(
            $unaryCallMock
        );

        $configService = new ConfigService($probillerConfigClientMock);

        $billerMappingService = new BillerMappingTranslatingService($configService);

        $billerMappingService->retrieveBillerMapping(
            'rocketgate',
            $this->faker->uuid,
            $this->faker->uuid,
            'CAD',
            $this->faker->uuid
        );
    }

    /**
     * @test
     *
     * @throws Exception
     * @throws BillerMappingException
     * @throws UnknownBillerNameException
     * @return void
     */
    public function it_should_throws_exception_when_got_invalid_biller_from_config_service(): void
    {
        $this->expectException(UnknownBillerNameException::class);
        $rocketgate = new RocketgateFields(
            [
                'merchantId'       => 'test-id',
                'merchantPassword' => 'test-pass',
                'merchantSiteId'   => '12341234'
            ]
        );

        $biller = new BillerData(
            [
                'name'         => $this->faker->name,
                'supports3DS1' => false,
                'supports3DS2' => false,
                'billerFields' => new BillerFields(['rocketgate' => $rocketgate])
            ]
        );

        /**
         * $availableCurrencies is an empty list, it will make throws a exception
         */
        $billerMappingResponse = new CommonBillerMapping(
            [
                'billerMappingId'     => $this->faker->uuid,
                'businessGroupId'     => $this->faker->uuid,
                'active'              => true,
                'siteId'              => $this->faker->uuid,
                'createdAt'           => new Timestamp(),
                'updatedAt'           => new Timestamp(),
                'availableCurrencies' => [
                    'USD',
                    'CAD'
                ],
                'biller'              => $biller,
            ]
        );

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new stdClass();
        $status->code = 0;

        $unaryCallMock->method('wait')->willReturn(
            [
                $billerMappingResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetBillerMappingConfigFiltered')->willReturn(
            $unaryCallMock
        );

        $configService = new ConfigService($probillerConfigClientMock);

        $billerMappingService = new BillerMappingTranslatingService($configService);

        $billerMappingService->retrieveBillerMapping(
            'rocketgate',
            $this->faker->uuid,
            $this->faker->uuid,
            'CAD',
            $this->faker->uuid
        );
    }
}