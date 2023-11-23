<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services;

use ProBillerNG\PurchaseGateway\Application\Exceptions\RetrieveNuDataScoreException;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataScoreRequestInfo;
use ProBillerNG\NuData\Infrastructure\Domain\Services\RetrieveNuDataScoreService;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\NuData\Infrastructure\Domain\Repository\ValueStoreNuDataSettingsRepository;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NuDataNotFoundException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\NuDataService;
use ProBillerNG\NuData\Infrastructure\Domain\Exceptions\NotFoundException;
use ProBillerNG\Logger\Exception;
use Tests\IntegrationTestCase;

class NuDataServiceTest extends IntegrationTestCase
{
    private const VALID_BUSINESS_GROUP_FOR_NU_DATA = '07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1';

    /**
     * @var NuDataService
     */
    private $nuDataService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $nuDataSettingsRepository   = new ValueStoreNuDataSettingsRepository();
        $retrieveNuDataScoreService = new RetrieveNuDataScoreService($nuDataSettingsRepository);
        $this->nuDataService        = new NuDataService($nuDataSettingsRepository, $retrieveNuDataScoreService);
    }

    /**
     * @test
     * @return void
     * @throws NotFoundException
     * @throws Exception
     * @throws NuDataNotFoundException
     */
    public function it_should_return_nu_data_settings_when_retrieve_settings_is_called(): void
    {
        $nuDataSettings = $this->nuDataService->retrieveSettings(self::VALID_BUSINESS_GROUP_FOR_NU_DATA);

        $this->assertInstanceOf(NuDataSettings::class, $nuDataSettings);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws NotFoundException
     * @throws NuDataNotFoundException
     */
    public function it_should_throw_nu_data_not_found_exception_when_business_group_is_not_found_in_nu_data_service(): void
    {
        $this->expectException(NuDataNotFoundException::class);

        $businessGroupId = $this->faker->uuid;
        $this->nuDataService->retrieveSettings($businessGroupId);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\RetrieveNuDataScoreException
     */
    public function it_should_return_a_string(): void
    {
        $nuDataScoreRequestInfo = new NuDataScoreRequestInfo(
            self::VALID_BUSINESS_GROUP_FOR_NU_DATA,
            $this->createNuDataEnvironmentData(),
            $this->createNuDataPurchasedProduct(),
            $this->createNuDataCard(),
            $this->createNuDataAccountInfoData(),
            $this->createNuDataCrossSales()
        );

        $nuDataScore = $this->nuDataService->retrieveScore($nuDataScoreRequestInfo);

        $this->assertIsString($nuDataScore);
        $this->assertTrue(!empty($nuDataScore));
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws RetrieveNuDataScoreException
     */
    public function it_should_throw_retrieve_nu_data_score_exception_when_business_group_is_not_found_in_nu_data_service(): void
    {
        $this->expectException(RetrieveNuDataScoreException::class);

        $nuDataScoreRequestInfo = new NuDataScoreRequestInfo(
            $this->faker->uuid,
            $this->createNuDataEnvironmentData(),
            $this->createNuDataPurchasedProduct(),
            $this->createNuDataCard(),
            $this->createNuDataAccountInfoData(),
            $this->createNuDataCrossSales()
        );
        $this->nuDataService->retrieveScore($nuDataScoreRequestInfo);
    }
}
