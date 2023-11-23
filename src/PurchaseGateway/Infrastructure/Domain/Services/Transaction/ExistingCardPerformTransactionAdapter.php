<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\ExistingCardPerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;
use ProbillerNG\TransactionServiceClient\Model\ExistingCardSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\NetbillingExistingCardSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\NetbillingMember;
use ProbillerNG\TransactionServiceClient\Model\PaymentTemplate;
use ProbillerNG\TransactionServiceClient\Model\PaymentTemplateBillerFields;
use ProbillerNG\TransactionServiceClient\Model\PaymentTemplateInformation;
use ProbillerNG\TransactionServiceClient\Model\Rebill;
use ProbillerNG\TransactionServiceClient\Model\NetbillingBillerFields;

class ExistingCardPerformTransactionAdapter extends BaseTransactionAdapter implements ExistingCardPerformTransactionInterfaceAdapter
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
     * @param string|null       $returnUrl         The 3ds return url
     * @param bool              $isNSFSupported    Flag to show if NSF is supported or not
     *                                             NOT USED. Because it uses the same interface as the new card,
     *                                             we need to pass it.
     *
     * @return Transaction
     * @throws Exception
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
                $response = $this->client->performRocketgateTransactionWithCardHash(
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
                        $returnUrl
                    ),
                    (string) $sessionId
                );
            } elseif ($biller->name() === NetbillingBiller::BILLER_NAME) {
                $response = $this->client->performNetbillingTransactionWithCardHash(
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
                throw new BillerNotSupportedException($biller->getValue());
            }

            $transaction = $this->translator->translate($response, false, $biller->name());
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
                false
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
     * @param bool|null         $useThreeDS        3ds flag
     * @param string|null       $returnUrl         Return URL for 3ds flow
     *
     * @return ExistingCardSaleRequestBody
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
        ?bool $useThreeDS = false,
        ?string $returnUrl = null
    ): ExistingCardSaleRequestBody {
        $existingCardSaleRequest = new ExistingCardSaleRequestBody();

        $clientPaymentInformation = (new PaymentTemplateInformation())
            ->setCardHash($paymentInfo->cardHash());

        $clientPayment = (new PaymentTemplate())
            ->setMethod($paymentInfo->paymentType())
            ->setInformation($clientPaymentInformation);

        $clientBillerFields = (new PaymentTemplateBillerFields())
            ->setMerchantId($billerMapping->billerFields()->merchantId())
            ->setMerchantPassword($billerMapping->billerFields()->merchantPassword())
            ->setMerchantSiteId($billerMapping->billerFields()->billerSiteId())
            ->setMerchantCustomerId($billerMapping->billerFields()->merchantCustomerId())
            ->setMerchantInvoiceId($billerMapping->billerFields()->merchantInvoiceId())
            ->setIpAddress((string) $userInfo->ipAddress())
            ->setSharedSecret($billerMapping->billerFields()->sharedSecret())
            ->setSimplified3DS($billerMapping->billerFields()->simplified3DS())
            ->setReferringMerchantId(
                ($this->getReferredMerchantId($paymentInfo) ?? $billerMapping->billerFields()->merchantId())
            );

        if (!is_null($binRouting) && !is_null($binRouting->routingCode())) {
            $clientBillerFields->setMerchantAccount($binRouting->routingCode());
        }

        $existingCardSaleRequest->setUseThreeD($useThreeDS);
        $existingCardSaleRequest->setReturnUrl($returnUrl);

        $existingCardSaleRequest->setSiteId((string) $siteId)
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

            $existingCardSaleRequest->setRebill($rebill);
        }

        return $existingCardSaleRequest;
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
     * @return NetbillingExistingCardSaleRequestBody
     */
    public function getNetbillingSaleRequest(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        ?BinRouting $binRouting
    ): NetbillingExistingCardSaleRequestBody {
        $existingCardSaleRequest = new NetbillingExistingCardSaleRequestBody();

        $clientPaymentInformation = (new PaymentTemplateInformation())
            ->setCardHash($paymentInfo->cardHash());

        $clientPayment = (new PaymentTemplate())
            ->setMethod($paymentInfo->paymentType())
            ->setInformation($clientPaymentInformation);

        $clientBillerFields = (new NetbillingBillerFields())
            ->setAccountId($billerMapping->billerFields()->accountId())
            ->setSiteTag($billerMapping->billerFields()->siteTag())
            ->setMerchantPassword($billerMapping->billerFields()->merchantPassword())
            ->setInitialDays($chargeInformation->validFor()->days())
            ->setIpAddress((string) $userInfo->ipAddress());

        if (!is_null($binRouting) && !is_null($binRouting->routingCode())) {
            $clientBillerFields->setBinRouting((string) $binRouting->routingCode());
        }

        // When netbilling creates a member to be consistent
        // we need to pass user name ans password
        $member = new NetbillingMember();
        // We have to explicitly cast it to string due to NetbillingMember client's auto-generated code
        $member->setUserName((string) $userInfo->username());
        $member->setPassword((string) $userInfo->password());

        $existingCardSaleRequest->setSiteId((string) $siteId)
            ->setBillerId($biller->id())
            ->setAmount($chargeInformation->initialAmount()->value())
            ->setCurrency($currency->getValue())
            ->setPayment($clientPayment)
            ->setBillerFields($clientBillerFields)
            ->setMember($member);

        if ($chargeInformation instanceof BundleRebillChargeInformation) {
            $rebill = (new Rebill())
                ->setAmount($chargeInformation->rebillAmount()->value())
                ->setFrequency($chargeInformation->repeatEvery()->days())
                ->setStart($chargeInformation->validFor()->days());

            $existingCardSaleRequest->setRebill($rebill);
        }

        return $existingCardSaleRequest;
    }

    /**
     * @param PaymentInfo $paymentInfo
     *
     * @return string|null
     */
    private function getReferredMerchantId(PaymentInfo $paymentInfo): ?string
    {
        if (!$paymentInfo instanceof ExistingCCPaymentInfo) {
            return null;
        }
        /**
         * @var $paymentInfo ExistingCCPaymentInfo
         */
        return $paymentInfo->billerFields()['merchantId'] ?? null;
    }
}
