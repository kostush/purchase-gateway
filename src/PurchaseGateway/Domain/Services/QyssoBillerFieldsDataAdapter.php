<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Illuminate\Support\Str;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;

class QyssoBillerFieldsDataAdapter extends BillerFieldsDataAdapter
{
    /**
     * @var string
     */
    private $companyNum;

    /**
     * @var string
     */
    private $personalHashKey;

    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return BillerFields
     * @throws InvalidBillerFieldsDataException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function convert(array $billerFieldsData): BillerFields
    {
        $this->initBillerFields($billerFieldsData);

        return QyssoBillerFields::create(
            $this->companyNum,
            $this->personalHashKey
        );
    }

    /**
     * @param array $billerFields Biller fields
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidBillerFieldsDataException
     */
    private function initBillerFields(array $billerFields): void
    {
        try {
            foreach ($billerFields as $key => $value) {
                $billerFields[Str::camel($key)] = $value;
            }

            $this->companyNum      = $billerFields['companyNum'];
            $this->personalHashKey = $billerFields['personalHashKey'];
        } catch (\Throwable $e) {
            Log::error('Error occurred while creating epoch biller fields data from array');
            throw new InvalidBillerFieldsDataException($e);
        }
    }
}
