<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\NewChequePerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;
use ProbillerNG\TransactionServiceClient\ApiException;
use ProbillerNG\TransactionServiceClient\Model\BillerFields as ClientBillerFields;
use ProbillerNG\TransactionServiceClient\Model\Check;
use ProbillerNG\TransactionServiceClient\Model\CheckInformation;
use ProbillerNG\TransactionServiceClient\Model\CheckMember;
use ProbillerNG\TransactionServiceClient\Model\Rebill;
use ProbillerNG\TransactionServiceClient\Model\RocketgateOtherPaymentTypeSaleRequestBody;

class NewChequePerformTransactionAdapter extends BaseTransactionAdapter implements NewChequePerformTransactionInterfaceAdapter
{
    /**
     * @param SiteId            $siteId
     * @param Biller            $biller
     * @param CurrencyCode      $currencyCode
     * @param UserInfo          $userInfo
     * @param ChargeInformation $chargeInformation
     * @param PaymentInfo       $paymentInfo
     * @param BillerMapping     $billerMapping
     * @param SessionId         $sessionId
     *
     * @return Transaction
     * @throws ApiException
     * @throws BillerNotSupportedException
     * @throws Exceptions\InvalidResponseException
     * @throws Exception
     */
    public function performTransaction(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId
    ): Transaction {
        if ($biller->name() === RocketgateBiller::BILLER_NAME) {
            $response = $this->client->performRocketgateTransactionWithNewCheque(
                $this->getRocketgateChequeSaleRequest(
                    $siteId,
                    $biller,
                    $currencyCode,
                    $userInfo,
                    $chargeInformation,
                    $paymentInfo,
                    $billerMapping
                ),
                (string) $sessionId
            );

            return $this->translator->translate($response, false, $biller->name());
        } else {
            throw new BillerNotSupportedException($biller->name());
        }
    }

    /**
     * Map transaction query to rocketgate sale request
     *
     * @param SiteId            $siteId            SiteId
     * @param Biller            $biller            Biller
     * @param CurrencyCode      $currency          Currency
     * @param UserInfo          $userInfo          UserInfo
     * @param ChargeInformation $chargeInformation ProductInfo
     * @param PaymentInfo       $paymentInfo       PaymentInfo
     * @param BillerMapping     $billerMapping     BillerFields
     *
     * @return rocketgateOtherPaymentTypeSaleRequestBody
     */
    private function getRocketgateChequeSaleRequest(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping
    ): rocketgateOtherPaymentTypeSaleRequestBody {
        $chequeSaleRequest = new rocketgateOtherPaymentTypeSaleRequestBody();

        // For all optional param we did this check to prevent sending empty string to TS as it's throwing error for empty string
        $phoneNumber = !empty($userInfo->phoneNumber()) ? (string) $userInfo->phoneNumber() : null;
        $email       = !empty($userInfo->email()) ? (string) $userInfo->email() : null;
        $username    = !empty($userInfo->username()) ? (string) $userInfo->username() : null;
        $password    = !empty($userInfo->password()) ? (string) $userInfo->password() : null;

        $clientMember = (new CheckMember())
            ->setFirstName((string) $userInfo->firstName())
            ->setLastName((string) $userInfo->lastName())
            ->setUserName($username)
            ->setPassword($password)
            ->setEmail($email)
            ->setPhone($phoneNumber)
            ->setAddress($userInfo->address())
            ->setZipCode((string) $userInfo->zipCode())
            ->setCity($userInfo->city())
            ->setState($userInfo->state())
            ->setCountry((string) $userInfo->countryCode());

        /** @var ChequePaymentInfo $paymentInfo */
        $clientChequeInformation = (new CheckInformation())
            ->setRoutingNumber((string) $paymentInfo->routingNumber())
            ->setAccountNumber((string) $paymentInfo->accountNumber())
            ->setSavingAccount((bool) $paymentInfo->savingAccount())
            ->setSocialSecurityLast4((string) $paymentInfo->socialSecurityLast4())
            ->setMember($clientMember);

        $clientPayment = (new Check())
            ->setType($paymentInfo->paymentType())
            ->setMethod($paymentInfo->paymentMethod())
            ->setInformation($clientChequeInformation);

        $billerSiteId = !empty($billerMapping->billerFields()->billerSiteId()) ? $billerMapping->billerFields()
            ->billerSiteId() : null;

        $billerCustomerId = !empty($billerMapping->billerFields()->merchantCustomerId()) ? $billerMapping->billerFields()
            ->merchantCustomerId() : null;

        $billerInvoiceId = !empty($billerMapping->billerFields()->merchantInvoiceId()) ? $billerMapping->billerFields()
            ->merchantInvoiceId() : null;

        $clientBillerFields = (new ClientBillerFields())
            ->setMerchantId($billerMapping->billerFields()->merchantId())
            ->setMerchantPassword($billerMapping->billerFields()->merchantPassword())
            ->setMerchantSiteId($billerSiteId)
            ->setMerchantCustomerId($billerCustomerId)
            ->setMerchantInvoiceId($billerInvoiceId)
            ->setIpAddress((string) $userInfo->ipAddress());

        $chequeSaleRequest->setSiteId((string) $siteId)
            ->setBillerId($biller->id())
            ->setAmount($chargeInformation->initialAmount()->value())
            ->setCurrency($currency->getValue())
            ->setPayment($clientPayment)
            ->setBillerFields($clientBillerFields);

        if ($chargeInformation instanceof BundleRebillChargeInformation) {
            $rebill = (new Rebill())
                ->setAmount($chargeInformation->rebillAmount()->value())
                ->setFrequency($chargeInformation->repeatEvery()->days())
                ->setStart($chargeInformation->validFor()->days());

            $chequeSaleRequest->setRebill($rebill);
        }

        return $chequeSaleRequest;
    }
}
