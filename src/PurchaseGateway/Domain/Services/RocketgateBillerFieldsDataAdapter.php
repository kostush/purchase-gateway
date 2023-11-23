<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Exception;
use Illuminate\Support\Str;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;
use Throwable;

class RocketgateBillerFieldsDataAdapter extends BillerFieldsDataAdapter
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $merchantPassword;

    /**
     * @var string
     */
    private $billerSiteId;

    /**
     * @var string
     */
    private $sharedSecret;

    /**
     * @var bool
     */
    private $simplified3DS;

    /**
     * @var string
     */
    private $merchantCustomerId;

    /**
     * @var string
     */
    private $merchantInvoiceId;

    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return BillerFields
     * @throws Exception
     */
    public function convert(array $billerFieldsData): BillerFields
    {
        $this->initBillerData($billerFieldsData);

        return RocketgateBillerFields::create(
            $this->merchantId,
            $this->merchantPassword,
            $this->billerSiteId,
            $this->sharedSecret,
            $this->simplified3DS,
            $this->merchantCustomerId,
            $this->merchantInvoiceId
        );
    }

    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return void
     * @throws Exception
     */
    private function initBillerData(array $billerFieldsData): void
    {
        try {
            foreach ($billerFieldsData as $key => $value) {
                $billerFieldsData[Str::camel($key)] = $value;
            }

            $this->merchantId       = $billerFieldsData['merchantId'];
            $this->merchantPassword = $billerFieldsData['merchantPassword'];

            /**
             * According investigation billerSiteId, merchantSiteId are called siteId by ConfigService returns
             * As we can check on \Probiller\Rocketgate\RocketgateFields
             */
            $this->billerSiteId = $billerFieldsData['billerSiteId']
                                   ?? $billerFieldsData['merchantSiteId']
                                   ?? $billerFieldsData['siteId'];

            $this->sharedSecret  = $billerFieldsData['sharedSecret'] ?? '';
            $this->simplified3DS = $billerFieldsData['simplified3DS'] ?? false;

            $this->merchantCustomerId = $billerFieldsData['merchantCustomerId'] ?? null;
            $this->merchantInvoiceId  = $billerFieldsData['merchantInvoiceId'] ?? null;
        } catch (Throwable $e) {
            Log::error('Error occurred while creating rocketgate biller fields data from array');
            throw new InvalidBillerFieldsDataException($e);
        }
    }
}
