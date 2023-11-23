<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Exception;
use Illuminate\Support\Str;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;

class NetbillingBillerFieldsDataAdapter extends BillerFieldsDataAdapter
{
    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $siteTag;

    /**
     * @var string|null
     */
    private $binRouting;

    /**
     * @var string|null
     */
    private $merchantPassword;

    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return BillerFields
     * @throws Exception
     */
    public function convert(array $billerFieldsData): BillerFields
    {
        $this->initBillerData($billerFieldsData);
        return NetbillingBillerFields::create(
            $this->accountId,
            $this->siteTag,
            $this->binRouting,
            $this->merchantPassword
        );
    }

    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return void
     * @throws Exception
     */
    private function initBillerData(array $billerFieldsData)
    {
        try {
            foreach ($billerFieldsData as $key => $value) {
                $billerFieldsData[Str::camel($key)] = $value;
            }

            $this->accountId        = $billerFieldsData['accountId'];
            $this->siteTag          = $billerFieldsData['siteTag'];
            $this->binRouting       = $billerFieldsData['binRouting'] ?? null;
            $this->merchantPassword = $billerFieldsData['merchantPassword'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Error occurred while creating netbilling biller fields data from array');
            throw new InvalidBillerFieldsDataException($e);
        }
    }
}
