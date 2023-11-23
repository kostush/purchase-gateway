<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use Illuminate\Support\Facades\Validator;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;

class NetbillingProcessPurchaseCommandValidator
{
    const STATE_FIELD_MAXIMIUM_LENGTH = 30;

    /**
     * @param ProcessPurchaseCommand $command ProcessPurchase Command
     * @return bool
     * @throws InvalidRequestException
     */
    public function validate(ProcessPurchaseCommand $command)
    {
        $this->throwExceptionOnStateisNull($command);
        $this->throwExceptionOnStateLengthIsInvalid($command);
        return true;
    }

    /**
     * @param ProcessPurchaseCommand $command ProcessPurchase Command
     * @throws InvalidRequestException
     * @return void
     */
    private function throwExceptionOnStateisNull(ProcessPurchaseCommand $command)
    {
        if ($command->state() == null) {
            $validator = Validator::make(
                ['state' => $command->state()],
                ['state' => 'required'],
                ['required' => 'State field was not provided']
            );

            throw new InvalidRequestException($validator);
        }
    }

    /**
     * @param ProcessPurchaseCommand $command ProcessPurchase Command
     * @throws InvalidRequestException
     * @return void
     */
    private function throwExceptionOnStateLengthIsInvalid(ProcessPurchaseCommand $command)
    {
        if (strlen($command->state()) > self::STATE_FIELD_MAXIMIUM_LENGTH) {
            $validator = Validator::make(
                ['state' => $command->state()],
                [],
                ['required' => 'State field length is invalid']
            );

            throw new InvalidRequestException($validator);
        }
    }
}