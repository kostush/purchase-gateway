<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\ConfigService;

use Google\Protobuf\StringValue;
use Grpc\ChannelCredentials;
use Probiller\Common\BusinessGroup;
use Probiller\Common\PaymentTemplateValidation;
use Probiller\Common\ServiceData;
use Probiller\Common\Site;
use Probiller\Service\Config\BusinessGroupResponse;
use Probiller\Service\Config\GetPaymentTemplateValidationRequest;
use Probiller\Service\Config\ProbillerConfigClient;
use Probiller\Service\Config\SiteResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\InvalidConfigServiceResponse;
use Tests\IntegrationTestCase;
use const Grpc\STATUS_OK;

/**
 * Class ConfigServiceTest
 * @package Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\ConfigService
 */
class ConfigServiceTest extends IntegrationTestCase
{
    /**
     * @var string
     */
    private $siteId;


    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->siteId = "8e34c94e-135f-4acb-9141-58b3a6e56c74";
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_payment_template_validation_information_from_config_service(): void
    {
        $templateOwnerFilterRequest = new GetPaymentTemplateValidationRequest\PaymentTemplateValidationOwnerFilter();
        $templateOwnerFilterRequest->setSiteId($this->siteId);

        $validationRequest = new GetPaymentTemplateValidationRequest();
        $validationRequest->setOwner($templateOwnerFilterRequest);

        $configService = app()->make(ConfigService::class);
        $client = (new ConfigService(
            new ProbillerConfigClient(
                env('CONFIG_SERVICE_HOST', 'host.docker.internal:5000'),
                ['credentials' => ChannelCredentials::createSsl()]
            )
        ))->getClient();

        /**
         * @var PaymentTemplateValidation $configReply
         */
        [
            $configReply,
            $responseStatus
        ] = $client->GetPaymentTemplateValidationConfig($validationRequest, $configService->getMetadata())->wait();

        $this->assertEquals(0, $responseStatus->code);
        $this->assertIsObject($configReply);
        $this->assertInstanceOf(PaymentTemplateValidation::class, $configReply);
        $this->assertSame($this->siteId, $configReply->getSiteId());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_not_throw_exception_when_config_service_return_site_with_non_required_data_missing(): void
    {
        /* Ok status definition */
        $status             = new \stdClass();
        $status->code       = STATUS_OK;
        $serviceDataName    = $this->faker->firstName;
        $serviceDataEnabled = true;

        /* Mocking background client */
        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        /* Mocking config-service response to BusinessGroup background data  */
        $businessGroup = new BusinessGroup(
            [
                'businessGroupId'  => $this->faker->uuid,
                'name'             => $this->faker->firstName,
                'description'      => $this->faker->text,
                'numberOfAttempts' => 2,
                'privateKey'       => new StringValue(['value' => $this->faker->uuid]),
                'publicKeys'       => [$this->faker->uuid],
            ]
        );

        $businessGroupResponse = new BusinessGroupResponse(['businessGroup' => $businessGroup]);

        $businessGroupUnaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $businessGroupUnaryCallMock->method('wait')->willReturn(
            [
                $businessGroupResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetBusinessGroupConfig')->willReturn(
            $businessGroupUnaryCallMock
        );

        /* Mocking config-service response to Site that will be tested  */
        $siteUnaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $siteId = $this->faker->uuid;

        $serviceData = new ServiceData(
            [
                'name'    => $serviceDataName,
                'enabled' => $serviceDataEnabled
            ]
        );

        $site         = new Site(
            [
                'siteId'            => $siteId,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => [$serviceData]
                // IMPORTANT: These data is not required for site creation
                // 'phoneNumber'        => '',
                // 'skypeNumber'        => '',
                // 'supportLink'        => '',
                // 'mailSupportLink'    => '',
                // 'messageSupportLink' => '',
                // 'isNsfSupported'     => false,
                // 'numberOfAttempts'   => 2,
                // 'isStickyGateway'    => false,
                // 'cancellationLink'   => '',
                // 'resetPasswordLink'  => '',
                // 'description'        => ''

            ]
        );
        $siteResponse = new SiteResponse(['site' => $site]);

        $siteUnaryCallMock->method('wait')->willReturn(
            [
                $siteResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetSiteConfig')->willReturn(
            $siteUnaryCallMock
        );


        /** Execution  */
        $configService = new ConfigService($probillerConfigClientMock);
        $siteResult    = $configService->getSite($siteId);

        /** @var Service $service */
        $service = $siteResult->serviceCollection()->get(0);

        /** Grant that the default boolean is false */
        $this->assertFalse($siteResult->isNsfSupported());
        $this->assertFalse($siteResult->isStickyGateway());
        $this->assertNotEmpty($siteResult->serviceCollection());
        $this->assertEquals($serviceDataName, $service->name());
        $this->assertEquals($serviceDataEnabled, $service->enabled());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_throw_exception_when_config_service_return_site_without_service_collection(): void
    {
        $this->expectException(InvalidConfigServiceResponse::class);
        /* Ok status definition */
        $status       = new \stdClass();
        $status->code = STATUS_OK;

        /* Mocking background client */
        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        /* Mocking config-service response to BusinessGroup background data  */
        $businessGroup = new BusinessGroup(
            [
                'businessGroupId'  => $this->faker->uuid,
                'name'             => $this->faker->firstName,
                'description'      => $this->faker->text,
                'numberOfAttempts' => 2,
                'privateKey'       => new StringValue(['value' => $this->faker->uuid]),
                'publicKeys'       => [$this->faker->uuid],
            ]
        );

        $businessGroupResponse = new BusinessGroupResponse(['businessGroup' => $businessGroup]);

        $businessGroupUnaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $businessGroupUnaryCallMock->method('wait')->willReturn(
            [
                $businessGroupResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetBusinessGroupConfig')->willReturn(
            $businessGroupUnaryCallMock
        );

        /* Mocking config-service response to Site that will be tested  */
        $siteUnaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $siteId = $this->faker->uuid;

        $site = new Site(
            [
                'siteId'            => $siteId,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => []
            ]
        );
        
        $siteResponse = new SiteResponse(['site' => $site]);

        $siteUnaryCallMock->method('wait')->willReturn(
            [
                $siteResponse,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetSiteConfig')->willReturn(
            $siteUnaryCallMock
        );

        /** Execution  */
        $configService = new ConfigService($probillerConfigClientMock);
        $configService->getSite($siteId);
    }
}