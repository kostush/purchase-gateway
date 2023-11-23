<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\NuData;

class NuDataScoreRequestInfo
{
    /**
     * @var string
     */
    private $businessGroupId;

    /**
     * @var NuDataEnvironmentData
     */
    private $nuDataEnvironmentData;

    /**
     * @var NuDataPurchasedProduct
     */
    private $nuDataPurchasedProduct;

    /**
     * @var NuDataCard
     */
    private $nuDataCard;

    /**
     * @var NuDataAccountInfoData
     */
    private $nuDataAccountInfoData;

    /**
     * @var NuDataCrossSales
     */
    private $nuDataCrossSales;

    /**
     * NuDataScoreRequestInfo constructor.
     * @param string                 $businessGroupId        Business Group Id
     * @param NuDataEnvironmentData  $nuDataEnvironmentData  NuData Environment Data
     * @param NuDataPurchasedProduct $nuDataPurchasedProduct NuData Purchased Product
     * @param NuDataCard             $nuDataCard             NuData Card
     * @param NuDataAccountInfoData  $nuDataAccountInfoData  NuData Account Info Data
     * @param NuDataCrossSales       $nuDataCrossSales       NuData Cross Sales
     */
    public function __construct(
        string $businessGroupId,
        NuDataEnvironmentData $nuDataEnvironmentData,
        NuDataPurchasedProduct $nuDataPurchasedProduct,
        NuDataCard $nuDataCard,
        NuDataAccountInfoData $nuDataAccountInfoData,
        NuDataCrossSales $nuDataCrossSales
    ) {
        $this->businessGroupId        = $businessGroupId;
        $this->nuDataEnvironmentData  = $nuDataEnvironmentData;
        $this->nuDataPurchasedProduct = $nuDataPurchasedProduct;
        $this->nuDataCard             = $nuDataCard;
        $this->nuDataAccountInfoData  = $nuDataAccountInfoData;
        $this->nuDataCrossSales       = $nuDataCrossSales;
    }

    /**
     * @return string
     */
    public function businessGroupId(): string
    {
        return $this->businessGroupId;
    }

    /**
     * @return NuDataEnvironmentData
     */
    public function nuDataEnvironmentData(): NuDataEnvironmentData
    {
        return $this->nuDataEnvironmentData;
    }

    /**
     * @return NuDataPurchasedProduct
     */
    public function nuDataPurchasedProduct(): NuDataPurchasedProduct
    {
        return $this->nuDataPurchasedProduct;
    }

    /**
     * @return NuDataCard
     */
    public function nuDataCard(): NuDataCard
    {
        return $this->nuDataCard;
    }

    /**
     * @return NuDataAccountInfoData
     */
    public function nuDataAccountInfoData(): NuDataAccountInfoData
    {
        return $this->nuDataAccountInfoData;
    }

    /**
     * @return NuDataCrossSales
     */
    public function nuDataCrossSales(): NuDataCrossSales
    {
        return $this->nuDataCrossSales;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $crossSales = [];

        /**
         * @var NuDataPurchasedProduct $crossSale
         */
        foreach ($this->nuDataCrossSales->productsList() as $crossSale) {
            $crossSales[] = [
                "subscriptionId"     => $crossSale->subscriptionId(),
                "isRecurring"        => $crossSale->isRecurring(),
                "isTrial"            => $crossSale->isTrial(),
                "price"              => $crossSale->price(),
                "bundleId"           => $crossSale->bundleId(),
                "purchaseSuccessful" => $crossSale->purchaseSuccessful()
            ];
        }
        return [
            "businessGroupId"        => $this->businessGroupId,
            "nuDataEnvironmentData"  => [
                "ndWidgetData"  => $this->nuDataEnvironmentData->ndWidgetData(),
                "xForwardedFor" => $this->nuDataEnvironmentData->xForwardedFor(),
                "userAgent"     => $this->nuDataEnvironmentData->userAgent(),
                "requestUrl"    => $this->nuDataEnvironmentData->requestUrl(),
                "remoteIp"      => $this->nuDataEnvironmentData->remoteIp(),
                "ndSesssionId"  => $this->nuDataEnvironmentData->ndSesssionId()
            ],
            "nuDataPurchasedProduct" => [
                "subscriptionId"     => $this->nuDataPurchasedProduct->subscriptionId(),
                "isRecurring"        => $this->nuDataPurchasedProduct->isRecurring(),
                "isTrial"            => $this->nuDataPurchasedProduct->isTrial(),
                "price"              => $this->nuDataPurchasedProduct->price(),
                "bundleId"           => $this->nuDataPurchasedProduct->bundleId(),
                "purchaseSuccessful" => $this->nuDataPurchasedProduct->purchaseSuccessful()
            ],
            "nuDataCard"             => [
                "cardNumber" => '**************',
                "holderName" => $this->nuDataCard->holderName()
            ],
            "nuDataAccountInfoData"  => [
                "lastName"  => $this->nuDataAccountInfoData->lastName(),
                "firstName" => $this->nuDataAccountInfoData->firstName(),
                "email"     => $this->nuDataAccountInfoData->email(),
                "username"  => $this->nuDataAccountInfoData->username(),
                "password"  => '***********',
                "phone"     => $this->nuDataAccountInfoData->phone(),
                "address"   => $this->nuDataAccountInfoData->address(),
                "city"      => $this->nuDataAccountInfoData->city(),
                "country"   => $this->nuDataAccountInfoData->country(),
                "zipCode"   => $this->nuDataAccountInfoData->zipCode()
            ],
            "nuDataCrossSales"       => $crossSales
        ];
    }
}
