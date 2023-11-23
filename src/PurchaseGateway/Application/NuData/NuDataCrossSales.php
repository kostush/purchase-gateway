<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\NuData;

class NuDataCrossSales
{
    /**
     * @var array
     */
    private $productsList;

    /**
     * NuDataCrossSales constructor.
     */
    public function __construct()
    {
        $this->productsList = [];
    }

    /**
     * @param NuDataPurchasedProduct $product NuData Purchased Product
     * @return void
     */
    public function addProduct(NuDataPurchasedProduct $product): void
    {
        $this->productsList[] = $product;
    }

    /**
     * @return array
     */
    public function productsList(): array
    {
        return $this->productsList;
    }
}
