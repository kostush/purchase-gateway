<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Illuminate\Support\Str;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException;

class EpochBillerFieldsDataAdapter extends BillerFieldsDataAdapter
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientKey;

    /**
     * @var string
     */
    private $clientVerificationKey;

    /**
     * @param array $billerFieldsData Biller Fields Data
     * @return BillerFields
     * @throws InvalidBillerFieldsDataException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function convert(array $billerFieldsData): BillerFields
    {
        $this->initBillerFields($billerFieldsData);

        return EpochBillerFields::create(
            $this->clientId,
            $this->clientKey,
            $this->clientVerificationKey
        );
    }

    /**
     * @param array $billerFields Biller fields
     * @throws InvalidBillerFieldsDataException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initBillerFields(array $billerFields): void
    {
        try {
            foreach ($billerFields as $key => $value) {
                $billerFields[Str::camel($key)] = $value;
            }

            $this->clientId              = $billerFields['clientId'];
            $this->clientKey             = $billerFields['clientKey'];
            $this->clientVerificationKey = $billerFields['clientVerificationKey'];
        } catch (\Throwable $e) {
            Log::error('Error occurred while creating epoch biller fields data from array');
            throw new InvalidBillerFieldsDataException($e);
        }
    }
}
