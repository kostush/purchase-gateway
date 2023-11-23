<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services;

use ProBillerNG\NuData\Domain\Model\AccountInfoData;
use ProBillerNG\NuData\Domain\Model\Card;
use ProBillerNG\NuData\Domain\Model\CrossSales;
use ProBillerNG\NuData\Domain\Model\EnvironmentData;
use ProBillerNG\NuData\Domain\Model\NuDataScoreRequest;
use ProBillerNG\NuData\Domain\Model\PurchasedProduct;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataAccountInfoData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCard;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCrossSales;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataEnvironmentData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataPurchasedProduct;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataScoreRequestInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService as NuDataServiceInterface;
use ProBillerNG\NuData\Domain\Repository\NuDataSettingsRepository;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NuDataNotFoundException;
use ProBillerNG\Logger\Exception;
use ProBillerNG\NuData\Infrastructure\Domain\Exceptions\NotFoundException;
use ProBillerNG\NuData\Domain\Services\RetrieveNuDataScoreService;
use ProBillerNG\PurchaseGateway\Application\Exceptions\RetrieveNuDataScoreException;

class NuDataService implements NuDataServiceInterface
{
    /**
     * @var NuDataSettingsRepository
     */
    private $nuDataSettingsRepository;

    /**
     * @var RetrieveNuDataScoreService
     */
    private $nuDataScoreService;

    /**
     * NuDataService constructor.
     * @param NuDataSettingsRepository   $nuDataSettingsRepository NuData Settings Repository
     * @param RetrieveNuDataScoreService $nuDataScoreService       NuData Score Service
     */
    public function __construct(
        NuDataSettingsRepository $nuDataSettingsRepository,
        RetrieveNuDataScoreService $nuDataScoreService
    ) {
        $this->nuDataSettingsRepository = $nuDataSettingsRepository;
        $this->nuDataScoreService       = $nuDataScoreService;
    }

    /**
     * @param string $businessGroupId Business Group Id
     *
     * @return NuDataSettings
     * @throws Exception
     * @throws NotFoundException
     * @throws NuDataNotFoundException
     */
    public function retrieveSettings(string $businessGroupId): NuDataSettings
    {
        try {
            $settings = $this->nuDataSettingsRepository->find($businessGroupId);

            $nuDataSettings = NuDataSettings::create(
                $settings->clientId(),
                $settings->url(),
                $settings->enabled()
            );

            return $nuDataSettings;
        } catch (NotFoundException $notFoundException) {
            throw new NuDataNotFoundException($businessGroupId);
        }
    }

    //this method return mocked NuDataScore and will be changed when the communication with nuDataService will be added.

    /**
     * @param NuDataScoreRequestInfo $nuDataScoreRequestInfo NuData Score Request Info
     * @return string
     * @throws Exception
     * @throws RetrieveNuDataScoreException
     */
    public function retrieveScore(NuDataScoreRequestInfo $nuDataScoreRequestInfo): string
    {
        try {
            /** @var NuDataEnvironmentData $nuDataEnvironmentData */
            $nuDataEnvironmentData = $nuDataScoreRequestInfo->nuDataEnvironmentData();
            /** @var NuDataPurchasedProduct $nuDataPurchasedProduct */
            $nuDataPurchasedProduct = $nuDataScoreRequestInfo->nuDataPurchasedProduct();
            /** @var NuDataCard $nuDataCard */
            $nuDataCard = $nuDataScoreRequestInfo->nuDataCard();
            /** @var NuDataAccountInfoData $nuDataAccountInfoData */
            $nuDataAccountInfoData = $nuDataScoreRequestInfo->nuDataAccountInfoData();
            /** @var NuDataCrossSales $nuDataCrossSales */
            $nuDataCrossSales = $nuDataScoreRequestInfo->nuDataCrossSales();

            $environmentData = new EnvironmentData(
                $nuDataEnvironmentData->ndSesssionId(),
                $nuDataEnvironmentData->ndWidgetData(),
                $nuDataEnvironmentData->remoteIp(),
                $nuDataEnvironmentData->requestUrl(),
                $nuDataEnvironmentData->userAgent(),
                $nuDataEnvironmentData->xForwardedFor()
            );

            $purchaseProduct = new PurchasedProduct(
                $nuDataPurchasedProduct->price(),
                $nuDataPurchasedProduct->bundleId(),
                PurchasedProduct::PRODUCT_TYPE_BUNDLE,
                $nuDataPurchasedProduct->purchaseSuccessful() ? PurchasedProduct::PRODUCT_PURCHASE_STATUS_SUCCESS : PurchasedProduct::PRODUCT_PURCHASE_STATUS_FAILED,
                $nuDataPurchasedProduct->subscriptionId(),
                $nuDataPurchasedProduct->isTrial(),
                $nuDataPurchasedProduct->isRecurring()
            );

            $card = new Card(
                $nuDataCard->holderName(),
                $nuDataCard->cardNumber()
            );

            $accountInfoData = new AccountInfoData(
                $nuDataAccountInfoData->username(),
                $nuDataAccountInfoData->password(),
                $nuDataAccountInfoData->email(),
                $nuDataAccountInfoData->firstName(),
                $nuDataAccountInfoData->lastName(),
                $nuDataAccountInfoData->phone(),
                $nuDataAccountInfoData->address(),
                $nuDataAccountInfoData->city(),
                $nuDataAccountInfoData->state(),
                $nuDataAccountInfoData->country(),
                $nuDataAccountInfoData->zipCode()
            );

            /** @var CrossSales $crossSales */
            $crossSales = $this->generateCrossSales($nuDataCrossSales);

            $nuDataScoreRequest = new NuDataScoreRequest(
                $nuDataScoreRequestInfo->businessGroupId(),
                $environmentData,
                $purchaseProduct,
                $card,
                $accountInfoData,
                $crossSales
            );

            /** @var string $nuDataScore */
            $nuDataScore = $this->nuDataScoreService->retrieveScore($nuDataScoreRequest);

            return $nuDataScore;
        } catch (\Exception $exception) {
            throw new RetrieveNuDataScoreException($nuDataEnvironmentData->ndSesssionId());
        }
    }

    /**
     * @param NuDataCrossSales $nuDataCrossSales NuData Cross Sales
     * @return CrossSales
     */
    private function generateCrossSales(NuDataCrossSales $nuDataCrossSales): CrossSales
    {
        $crossSales = new CrossSales();

        foreach ($nuDataCrossSales->productsList() as $crossSale) {
            $purchasedCrossSale = new PurchasedProduct(
                $crossSale->price(),
                $crossSale->bundleId(),
                PurchasedProduct::PRODUCT_TYPE_BUNDLE,
                $crossSale->purchaseSuccessful() ? PurchasedProduct::PRODUCT_PURCHASE_STATUS_SUCCESS : PurchasedProduct::PRODUCT_PURCHASE_STATUS_FAILED,
                $crossSale->subscriptionId(),
                $crossSale->isTrial(),
                $crossSale->isRecurring()
            );

            $crossSales->addProduct($purchasedCrossSale);
        }

        return $crossSales;
    }
}
