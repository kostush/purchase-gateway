<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\NewCardPerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;
use ProbillerNG\TransactionServiceClient\Model\BillerFields as ClientBillerFields;
use ProbillerNG\TransactionServiceClient\Model\NetbillingBillerFields;
use ProbillerNG\TransactionServiceClient\Model\CreditCard;
use ProbillerNG\TransactionServiceClient\Model\CreditCardInformation;
use ProbillerNG\TransactionServiceClient\Model\Member;
use ProbillerNG\TransactionServiceClient\Model\NetBillingSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\Rebill;
use ProbillerNG\TransactionServiceClient\Model\SaleRequestBody;

class NewCardPerformTransactionAdapter extends BaseTransactionAdapter implements NewCardPerformTransactionInterfaceAdapter
{
    /**
     * @param SiteId            $siteId            SiteId
     * @param Biller            $biller            Biller
     * @param CurrencyCode      $currency          Currency
     * @param UserInfo          $userInfo          UserInfo
     * @param ChargeInformation $chargeInformation ChargeInformation
     * @param PaymentInfo       $paymentInfo       PaymentInfo
     * @param BillerMapping     $billerMapping     BillerMapping
     * @param SessionId         $sessionId         SessionId
     * @param BinRouting|null   $binRouting        Bin routing collection
     * @param bool              $useThreeD         Perform transaction using 3DS
     * @param string|null       $returnUrl         The return URL for the simplified 3ds flow
     * @param bool              $isNSFSupported    Flag to show if NSF is supported or not
     *
     * @return Transaction
     * @throws LoggerException
     */
    public function performTransaction(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        ?BinRouting $binRouting,
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): Transaction {
        try {
            if ($biller->name() === RocketgateBiller::BILLER_NAME) {
                $response = $this->client->performRocketgateTransactionWithNewCard(
                    $this->getRocketgateSaleRequest(
                        $siteId,
                        $biller,
                        $currency,
                        $userInfo,
                        $chargeInformation,
                        $paymentInfo,
                        $billerMapping,
                        $binRouting,
                        $useThreeD,
                        $returnUrl,
                        $isNSFSupported
                    ),
                    (string) $sessionId
                );
            } elseif ($biller->name() === NetbillingBiller::BILLER_NAME) {
                $response = $this->client->performNetbillingTransactionWithNewCard(
                    $this->getNetbillingSaleRequest(
                        $siteId,
                        $biller,
                        $currency,
                        $userInfo,
                        $chargeInformation,
                        $paymentInfo,
                        $billerMapping,
                        $binRouting
                    ),
                    (string) $sessionId
                );
            } else {
                throw new BillerNotSupportedException($biller->name());
            }

            $transaction = $this->translator->translate($response, true, $biller->name());
            $transaction->addSuccessfulBinRouting($binRouting);

            return $transaction;
        } catch (\Exception $e) {
            //log api exception
            Log::info('Transaction api exception');
            Log::logException($e);

            return Transaction::create(
                null,
                Transaction::STATUS_ABORTED,
                $biller->name(),
                true
            );
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
     * @param BinRouting|null   $binRouting        BinRouting
     * @param bool              $useThreeD         Perform transaction using 3DS
     * @param string|null       $returnUrl         The return URL for 3DS simplified flow
     * @param bool              $isNSFSupported    Flag to show if NSF is supported or not
     *
     * @return SaleRequestBody
     */
    private function getRocketgateSaleRequest(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        ?BinRouting $binRouting,
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): SaleRequestBody {
        $saleRequest = new SaleRequestBody();

        $clientMember = (new Member())
            ->setFirstName((string) $userInfo->firstName())
            ->setLastName((string) $userInfo->lastName())
            ->setEmail((string) $userInfo->email())
            ->setPhone((string) $userInfo->phoneNumber())
            ->setAddress($userInfo->address())
            ->setZipCode((string) $userInfo->zipCode())
            ->setCity($userInfo->city())
            ->setState($userInfo->state())
            ->setCountry((string) $userInfo->countryCode());

        $clientPaymentInformation = (new CreditCardInformation())
            ->setNumber($paymentInfo->ccNumber())
            ->setExpirationMonth($paymentInfo->expirationMonth())
            ->setExpirationYear($paymentInfo->expirationYear())
            ->setCvv($paymentInfo->cvv())
            ->setMember($clientMember);

        $clientPayment = (new CreditCard())
            ->setMethod($paymentInfo->paymentType())
            ->setInformation($clientPaymentInformation);

        $clientBillerFields = (new ClientBillerFields())
            ->setMerchantId($billerMapping->billerFields()->merchantId())
            ->setMerchantPassword($billerMapping->billerFields()->merchantPassword())
            ->setMerchantSiteId($billerMapping->billerFields()->billerSiteId())
            ->setSharedSecret($billerMapping->billerFields()->sharedSecret())
            ->setSimplified3DS($billerMapping->billerFields()->simplified3DS())
            ->setMerchantCustomerId($billerMapping->billerFields()->merchantCustomerId())
            ->setMerchantInvoiceId($billerMapping->billerFields()->merchantInvoiceId())
            ->setIpAddress((string) $userInfo->ipAddress());

        if (!is_null($binRouting) && !is_null($binRouting->routingCode())) {
            $clientBillerFields->setMerchantAccount($binRouting->routingCode());
        }

        $saleRequest->setSiteId((string) $siteId)
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

            $saleRequest->setRebill($rebill);
        }

        $saleRequest->setUseThreeD($useThreeD);
        $saleRequest->setReturnUrl($returnUrl);

        $saleRequest->setIsNSFSupported($isNSFSupported);

        return $saleRequest;
    }

    /**
     * @param SiteId            $siteId            SiteId
     * @param Biller            $biller            Biller
     * @param CurrencyCode      $currency          Currency
     * @param UserInfo          $userInfo          UserInfo
     * @param ChargeInformation $chargeInformation ChargeInformation
     * @param PaymentInfo       $paymentInfo       PaymentInfo
     * @param BillerMapping     $billerMapping     BillerMapping
     * @param BinRouting|null   $binRouting        Bin routing collection
     * @return NetBillingSaleRequestBody
     */
    private function getNetbillingSaleRequest(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        ?BinRouting $binRouting
    ): NetBillingSaleRequestBody {
        $saleRequest = new NetBillingSaleRequestBody();

        $clientMember = (new Member())
            ->setFirstName((string) $userInfo->firstName())
            ->setLastName((string) $userInfo->lastName())
            ->setUserName((string) $userInfo->username())
            ->setPassword((string) $userInfo->password())
            ->setEmail((string) $userInfo->email())
            ->setPhone((string) $userInfo->phoneNumber())
            ->setAddress($userInfo->address())
            ->setZipCode((string) $userInfo->zipCode())
            ->setCity($userInfo->city())
            ->setState($userInfo->state())
            ->setCountry((string) $userInfo->countryCode());

        $clientPaymentInformation = (new CreditCardInformation())
            ->setNumber($paymentInfo->ccNumber())
            ->setExpirationMonth($paymentInfo->expirationMonth())
            ->setExpirationYear($paymentInfo->expirationYear())
            ->setCvv($paymentInfo->cvv())
            ->setMember($clientMember);

        $clientPayment = (new CreditCard())
            ->setMethod($paymentInfo->paymentType())
            ->setInformation($clientPaymentInformation);

        $clientBillerFields = (new NetbillingBillerFields())
            ->setAccountId($billerMapping->billerFields()->accountId())
            ->setSiteTag($billerMapping->billerFields()->siteTag())
            ->setMerchantPassword($billerMapping->billerFields()->merchantPassword())
            ->setInitialDays($chargeInformation->validFor()->days())
            ->setIpAddress((string) $userInfo->ipAddress())
            ->setDisableFraudChecks($billerMapping->billerFields()->disableFraudChecks());

        if (!is_null($binRouting) && !is_null($binRouting->routingCode())) {
            $clientBillerFields->setBinRouting((string) $binRouting->routingCode());
        }

        $saleRequest->setSiteId((string) $siteId)
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

            $saleRequest->setRebill($rebill);
        }

        return $saleRequest;
    }
}
