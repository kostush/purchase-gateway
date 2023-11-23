<?php

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;

class UserInfoService
{
    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param array           $payload         Payload
     * @return void
     * @throws Exception
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidZipCodeException
     * @throws InvalidUserInfoPhoneNumber
     */
    public function update(PurchaseProcess $purchaseProcess, array $payload): void
    {
        if (!$purchaseProcess->wasMemberIdGenerated()) {
            return;
        }

        switch ((string) $purchaseProcess->cascade()->currentBiller()) {
            case QyssoBiller::BILLER_NAME:
                Log::info(
                    'User information received from third party biller',
                    [
                        'name'        => $payload['client_fullname'] ?? '',
                        'email'       => $payload['client_email'] ?? '',
                        'phoneNumber' => $payload['client_phone'] ?? '',
                    ]
                );

                break;
            case EpochBiller::BILLER_NAME:
            default:
                $name  = $payload['name'] ?? '';
                $email = $payload['email'] ?? '';
                $zip   = $payload['zip'] ?? '';
        }

        if (!empty($name)) {
            $fullName = $this->splitName($name);

            if (!empty($fullName['lastName'])) {
                $purchaseProcess->userInfo()->setLastName(LastName::create($fullName['lastName']));
            }

            if (!empty($fullName['firstName'])) {
                $purchaseProcess->userInfo()->setFirstName(FirstName::create($fullName['firstName']));
            }
        }

        if (!empty($email)) {
            $purchaseProcess->userInfo()->setEmail(Email::create($email));
        }

        if (!empty($zip)) {
            $purchaseProcess->userInfo()->setZipCode(Zip::create($zip));
        }
    }

    /**
     * @param string $name Full name
     * @return array
     */
    private function splitName(string $name): array
    {
        $parts = explode(' ', $name);

        if (count($parts) > 1) {
            $fullName['lastName']  = array_pop($parts);
            $fullName['firstName'] = implode(' ', $parts);
        } else {
            $fullName['firstName'] = $name;
        }

        return $fullName;
    }
}
