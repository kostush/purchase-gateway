<?php

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors;

use Grpc\UnaryCall;
use PHPUnit\Framework\MockObject\MockObject;
use Probiller\Common\BusinessGroup;
use Probiller\Common\ServiceData;
use Probiller\Common\Site;
use Probiller\Service\Config\BusinessGroupList;
use Probiller\Service\Config\BusinessGroupResponse;
use Probiller\Service\Config\ProbillerConfigClient;
use Probiller\Service\Config\SiteList;
use Probiller\Service\Config\SiteResponse;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors\CorsService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\ConfigServiceNotOk;
use stdClass;
use Tests\IntegrationTestCase;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_INTERNAL;

class CorsServiceTest extends IntegrationTestCase
{

    /**
     * @param SiteList|null          $siteList              Site list
     * @param BusinessGroupList|null $businessGroupList     Business Group list
     * @param int                    $statusCodeForSiteList Status code for site list
     * @param int                    $statusCodeForBGList   Status code for BG list
     *
     * @return MockObject|ProbillerConfigClient
     */
    public function buildProbillerConfigClientMock(
        SiteList $siteList = null,
        BusinessGroupList $businessGroupList = null,
        int $statusCodeForSiteList = STATUS_OK,
        int $statusCodeForBGList = STATUS_OK
    ) {
        $statusForSiteList          = new stdClass();
        $statusForSiteList->code    = $statusCodeForSiteList;
        $statusForSiteList->details = 'Test';

        $statusForBGList          = new stdClass();
        $statusForBGList->code    = $statusCodeForBGList;
        $statusForBGList->details = 'Test';

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $allSiteUnaryCallMock = $this->buildAllSiteUnaryCallMock($siteList, $statusForSiteList);
        $probillerConfigClientMock->method('GetAllSiteConfigs')->willReturn($allSiteUnaryCallMock);

        $allBGUnaryCallMock = $this->buildAllBusinessGroupUnaryCallMock($businessGroupList, $statusForBGList);
        $probillerConfigClientMock->method('GetAllBusinessGroupConfigs')->willReturn($allBGUnaryCallMock);

        return $probillerConfigClientMock;
    }

    /**
     * @param BusinessGroupList|null $businessGroupList Business Group list
     * @param stdClass               $status            Status
     *
     * @return MockObject
     */
    private function buildAllBusinessGroupUnaryCallMock(
        ?BusinessGroupList $businessGroupList,
        stdClass $status
    ): MockObject {
        /* Mocking config-service response to Business Group that will be tested  */
        $allBGUnaryCallMock = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bg = new BusinessGroup(
            [
                'businessGroupId'  => $this->faker->uuid,
                'name'             => $this->faker->firstName,
                'description'      => $this->faker->text,
                'numberOfAttempts' => 10,
                'allowedDomains'   => ['http://brazzers.com'],
            ]
        );

        $bgResponse = new BusinessGroupResponse(['businessGroup' => $bg]);

        if (!$businessGroupList) {
            $businessGroupList = new BusinessGroupList();
            $businessGroupList->setValue([$bgResponse]);
        }
        $allBGUnaryCallMock->method('wait')->willReturn(
            [
                $businessGroupList,
                $status,
            ]
        );

        return $allBGUnaryCallMock;
    }

    /**
     * @param SiteList|null $siteList Site list
     * @param stdClass      $status   Status
     *
     * @return MockObject
     */
    private function buildAllSiteUnaryCallMock(
        ?SiteList $siteList,
        stdClass $status
    ): MockObject {
        $serviceDataName = $this->faker->firstName;
        $serviceData     = new ServiceData(
            [
                'name'    => $serviceDataName,
                'enabled' => true,
            ]
        );

        /* Mocking config-service response to Site that will be tested  */
        $allSiteUnaryCallMock = $this->getMockBuilder(UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site = new Site(
            [
                'siteId'            => $this->faker->uuid,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => [$serviceData],
                'allowedDomains'    => ['http://mofosexpremium.com'],
            ]
        );

        $siteResponse = new SiteResponse(['site' => $site]);

        if (!$siteList) {
            $siteList = new SiteList();
            $siteList->setValue([$siteResponse]);
        }

        $allSiteUnaryCallMock->method('wait')->willReturn(
            [
                $siteList,
                $status,
            ]
        );

        return $allSiteUnaryCallMock;
    }

    /**
     * @test
     * @return void
     * @throws ConfigServiceNotOk
     */
    public function it_should_return_well_formatted_domain_list()
    {
        $probillerConfigClientMock = $this->buildProbillerConfigClientMock();
        $probillerConfigClientMock->expects($this->once())
            ->method('GetAllSiteConfigs');

        // Execution
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);
        $corsService = new CorsService($probillerConfigClientMock);
        $domains     = $corsService->getAllowedDomains();

        $this->assertEquals(
            [
                '*.mofosexpremium.com',
                '*.brazzers.com',
            ],
            $domains
        );
    }

    /**
     * @test
     * @return void
     * @throws ConfigServiceNotOk
     */
    public function it_should_get_domains_from_cache_when_domains_are_cached()
    {
        $probillerConfigClientMock = $this->buildProbillerConfigClientMock();

        $probillerConfigClientMock->expects($this->once())
            ->method('GetAllSiteConfigs');

        //Clear cache
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);

        // Execution
        $corsService = new CorsService($probillerConfigClientMock);

        //Getting firs time from config-service
        $corsService->getAllowedDomains();

        //Getting 2 times from cache
        $corsService->getAllowedDomains();
        $corsService->getAllowedDomains();
    }

    /**
     * @test
     * @return void
     * @throws ConfigServiceNotOk
     */
    public function it_should_get_domains_from_config_service_when_cache_is_empty()
    {
        $probillerConfigClientMock = $this->buildProbillerConfigClientMock();

        // expects that it will be called each time because the cache is cleared before each call.
        $probillerConfigClientMock->expects($this->exactly(3))
            ->method('GetAllSiteConfigs');

        // Execution
        $corsService = new CorsService($probillerConfigClientMock);

        // Call 1
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);
        $corsService->getAllowedDomains();

        // Call 2
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);
        $corsService->getAllowedDomains();

        // Call 3
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);
        $corsService->getAllowedDomains();
    }

    /**
     * @test
     * @return void
     * @throws ConfigServiceNotOk
     */
    public function it_should_get_domains_from_business_group()
    {
        $bg = new BusinessGroup(
            [
                'businessGroupId'  => $this->faker->uuid,
                'name'             => $this->faker->firstName,
                'description'      => $this->faker->text,
                'numberOfAttempts' => 10,
                'allowedDomains'   => ['http://businessgrouptest.com'],
            ]
        );

        $bgResponse        = new BusinessGroupResponse(['businessGroup' => $bg]);
        $businessGroupList = new BusinessGroupList();
        $businessGroupList->setValue([$bgResponse]);

        $siteList = new SiteList();
        $siteList->setValue([]);

        $probillerConfigClientMock = $this->buildProbillerConfigClientMock($siteList, $businessGroupList);

        // Execution
        $corsService = new CorsService($probillerConfigClientMock);

        // Call 1
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);

        $domains = $corsService->getAllowedDomains();

        $this->assertEquals(
            [
                '*.businessgrouptest.com',
            ],
            $domains
        );
    }

    /**
     * @test
     * @return void
     * @throws ConfigServiceNotOk
     */
    public function it_should_get_domains_from_site()
    {
        $site = new Site(
            [
                'siteId'            => $this->faker->uuid,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => [],
                'allowedDomains'    => ['http://sitetest.com'],
            ]
        );

        $siteResponse = new SiteResponse(['site' => $site]);
        $siteList     = new SiteList();
        $siteList->setValue([$siteResponse]);

        $bgList = new BusinessGroupList();
        $bgList->setValue([]);

        $probillerConfigClientMock = $this->buildProbillerConfigClientMock($siteList, $bgList);

        // Execution
        $corsService = new CorsService($probillerConfigClientMock);

        // Call 1
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);

        $domains = $corsService->getAllowedDomains();

        $this->assertEquals(
            [
                '*.sitetest.com',
            ],
            $domains
        );
    }

    /**
     * @test
     * @return void
     * @throws ConfigServiceNotOk
     */
    public function it_should_get_once_when_it_has_from_both_site_and_business_group()
    {
        $site = new Site(
            [
                'siteId'            => $this->faker->uuid,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => [],
                'allowedDomains'    => ['http://duplicateddomain.com'],
            ]
        );

        $siteResponse = new SiteResponse(['site' => $site]);
        $siteList     = new SiteList();
        $siteList->setValue([$siteResponse]);

        $bg = new BusinessGroup(
            [
                'businessGroupId'  => $this->faker->uuid,
                'name'             => $this->faker->firstName,
                'description'      => $this->faker->text,
                'numberOfAttempts' => 10,
                'allowedDomains'   => ['http://duplicateddomain.com'],
            ]
        );

        $bgResponse        = new BusinessGroupResponse(['businessGroup' => $bg]);
        $businessGroupList = new BusinessGroupList();
        $businessGroupList->setValue([$bgResponse]);

        $probillerConfigClientMock = $this->buildProbillerConfigClientMock($siteList, $businessGroupList);

        // Execution
        $corsService = new CorsService($probillerConfigClientMock);

        // Call
        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);

        $domains = $corsService->getAllowedDomains();

        $this->assertEquals(
            [
                '*.duplicateddomain.com',
            ],
            $domains
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_throw_exception_for_bg_list_when_config_service_is_unresponsive()
    {
        $this->expectException(ConfigServiceNotOk::class);

        $site = new Site(
            [
                'siteId'            => $this->faker->uuid,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => [],
                'allowedDomains'    => ['http://duplicateddomain.com'],
            ]
        );

        $siteResponse = new SiteResponse(['site' => $site]);
        $siteList     = new SiteList();
        $siteList->setValue([$siteResponse]);

        $probillerConfigClientMock = $this->buildProbillerConfigClientMock($siteList, null, STATUS_OK, STATUS_INTERNAL);

        $corsService = new CorsService($probillerConfigClientMock);

        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);

        $corsService->getAllowedDomains();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_throw_exception_for_site_list_when_config_service_is_unresponsive()
    {
        $this->expectException(ConfigServiceNotOk::class);

        $site = new Site(
            [
                'siteId'            => $this->faker->uuid,
                'businessGroupId'   => $this->faker->uuid,
                'name'              => $this->faker->firstName,
                'url'               => $this->faker->url,
                'postbackUrl'       => $this->faker->url,
                'serviceCollection' => [],
                'allowedDomains'    => ['http://duplicateddomain.com'],
            ]
        );

        $siteResponse = new SiteResponse(['site' => $site]);
        $siteList     = new SiteList();
        $siteList->setValue([$siteResponse]);

        $probillerConfigClientMock = $this->buildProbillerConfigClientMock($siteList, null, STATUS_INTERNAL, STATUS_OK);

        $corsService = new CorsService($probillerConfigClientMock);

        apcu_delete(CorsService::ALLOWED_DOMAIN_CACHE_KEY);

        $corsService->getAllowedDomains();
    }
}
