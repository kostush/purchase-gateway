<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxType;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformThirdPartyTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBody;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyBillerFields;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyCrossSales;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyPayment;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyPaymentInformation;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyPaymentInformationMember;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyRebill;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyTax;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyTaxInitialAmount;
use ProbillerNG\TransactionServiceClient\Model\QyssoTransactionRequestBody;
use ProbillerNG\TransactionServiceClient\Model\QyssoTransactionRequestBodyBillerFields;
use ProbillerNG\TransactionServiceClient\Model\QyssoTransactionRequestBodyPayment;

class ThirdPartyPerformTransactionAdapter extends BaseTransactionAdapter implements PerformThirdPartyTransactionAdapter
{
    /**
     * @param Site                $site              Site.
     * @param array               $crossSaleSites    Cross sales sites.
     * @param Biller              $biller            Biller.
     * @param CurrencyCode        $currency          Currency.
     * @param UserInfo            $userInfo          User info.
     * @param ChargeInformation   $chargeInformation Charge information.
     * @param PaymentInfo         $paymentInfo       Payment info.
     * @param BillerMapping       $billerMapping     Biller mapping.
     * @param SessionId           $sessionId         Session id.
     * @param string              $redirectUrl       Redirect url.
     * @param TaxInformation|null $taxInformation    Tax information.
     * @param array|null          $crossSales        Cross sales list.
     * @param string|null         $paymentMethod     Payment method.
     * @param string|null         $billerMemberId    Biller member id.
     * @return ThirdPartyTransaction
     * @throws Exception
     */
    public function performTransaction(
        Site $site,
        array $crossSaleSites,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        string $redirectUrl,
        ?TaxInformation $taxInformation,
        ?array $crossSales,
        ?string $paymentMethod,
        ?string $billerMemberId
    ): ThirdPartyTransaction {
        try {
            switch ($biller->name()) {
                case EpochBiller::BILLER_NAME:
                    $response = $this->client->performEpochTransaction(
                        $this->getEpochSaleRequest(
                            $site,
                            $crossSaleSites,
                            $biller,
                            $currency,
                            $userInfo,
                            $chargeInformation,
                            $paymentInfo,
                            $billerMapping,
                            $redirectUrl,
                            $taxInformation,
                            $crossSales,
                            $paymentMethod,
                            $billerMemberId
                        ),
                        (string) $sessionId
                    );
                    break;
                case QyssoBiller::BILLER_NAME:
                    $response = $this->client->performQyssoTransaction(
                        $this->getQyssoSaleRequest(
                            (string) $sessionId,
                            $site,
                            $biller,
                            $currency,
                            $userInfo,
                            $chargeInformation,
                            $paymentInfo,
                            $billerMapping,
                            $redirectUrl,
                            $taxInformation,
                            $paymentMethod,
                            $billerMemberId
                        ),
                        (string) $sessionId
                    );
                    break;
                default:
                    throw new BillerNotSupportedException($biller->name());
            }

            $transaction = $this->translator->translateThirdPartyResponse($response, $biller->name());

            return $transaction;
        } catch (Exception $e) {
            //log api exception
            Log::info('Transaction api exception');
            Log::logException($e);

            return ThirdPartyTransaction::create(
                null,
                Transaction::STATUS_ABORTED,
                $biller->name(),
                null
            );
        }
    }

    /**
     * Map transaction query to epoch sale request
     *
     * @param Site                $site              Site.
     * @param array               $crossSaleSites    Cross sales sites.
     * @param Biller              $biller            Biller.
     * @param CurrencyCode        $currency          Currency.
     * @param UserInfo            $userInfo          User info.
     * @param ChargeInformation   $chargeInformation Product info.
     * @param PaymentInfo         $paymentInfo       Payment info.
     * @param BillerMapping       $billerMapping     Biller fields.
     * @param string              $redirectUrl       Redirect url.
     * @param TaxInformation|null $taxInformation    Tax information.
     * @param array|null          $crossSales        Cross sales list.
     * @param string|null         $paymentMethod     Payment method.
     * @param string|null         $billerMemberId    Biller member id.
     * @return EpochTransactionRequestBody
     */
    private function getEpochSaleRequest(
        Site $site,
        array $crossSaleSites,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        string $redirectUrl,
        ?TaxInformation $taxInformation,
        ?array $crossSales,
        ?string $paymentMethod,
        ?string $billerMemberId
    ): EpochTransactionRequestBody {
        $saleRequest = new EpochTransactionRequestBody();

        $notificationUrl = env('POSTBACK_SERVICE_HOST') .
                           '/api/v1/postback/' .
                           $billerMapping->billerFields()->postbackId();

        $clientBillerFields = (new EpochTransactionRequestBodyBillerFields())
            ->setClientId($billerMapping->billerFields()->clientId())
            ->setClientKey($billerMapping->billerFields()->clientKey())
            ->setClientVerificationKey($billerMapping->billerFields()->clientVerificationKey())
            ->setRedirectUrl($redirectUrl)
            ->setNotificationUrl($notificationUrl);

        $saleRequest->setSiteId((string) $site->id())
            ->setSiteName((string) $site->url())
            ->setBillerId($biller->id())
            ->setAmount($chargeInformation->initialAmount()->value())
            ->setCurrency($currency->getValue())
            ->setBillerFields($clientBillerFields);

        $saleRequest->setPayment(
            $this->setPaymentInfo(
                $userInfo,
                $paymentInfo->paymentType(),
                $paymentMethod,
                $billerMemberId
            )
        );

        $this->updateSaleInformation($saleRequest, $chargeInformation, $taxInformation);

        if (is_null($crossSales)) {
            return $saleRequest;
        }

        $crossSalesRequest = [];

        foreach ($crossSales as $crossSale) {
            $crossSaleRequest = new EpochTransactionRequestBodyCrossSales();
            $crossSaleRequest->setPrechecked(true);

            $crossSaleSite = $crossSaleSites[$crossSale['id']];

            $crossSaleRequest->setSiteId((string) $crossSaleSite->id());
            $crossSaleRequest->setSiteName((string) $crossSaleSite->url());

            $crossSaleCharge = $crossSale['chargeInformation'];
            $crossSaleRequest->setAmount($crossSaleCharge->initialAmount()->value());

            $this->updateSaleInformation(
                $crossSaleRequest,
                $crossSaleCharge,
                $crossSale['taxInformation']
            );

            $crossSalesRequest[] = $crossSaleRequest;
        }

        $saleRequest->setCrossSales($crossSalesRequest);

        return $saleRequest;
    }

    /**
     * @param EpochTransactionRequestBody|EpochTransactionRequestBodyCrossSales|QyssoTransactionRequestBody $saleRequest       Sale data
     * @param ChargeInformation                                                                             $chargeInformation ProductInfo
     * @param TaxInformation|null                                                                           $taxInformation    Tax Information
     * @return void
     */
    private function updateSaleInformation(
        $saleRequest,
        ChargeInformation $chargeInformation,
        ?TaxInformation $taxInformation
    ): void {
        $taxBreakDown = $chargeInformation->fullTaxBreakDownArray();

        $taxInfo = new EpochTransactionRequestBodyTax();

        if (isset($taxBreakDown['initialAmount'])) {
            $initAmount = new EpochTransactionRequestBodyTaxInitialAmount($taxBreakDown['initialAmount']);
            $taxInfo->setInitialAmount($initAmount);
        }
        if ($chargeInformation instanceof BundleRebillChargeInformation) {
            $rebill = (new EpochTransactionRequestBodyRebill())
                ->setAmount($chargeInformation->rebillAmount()->value())
                ->setFrequency($chargeInformation->repeatEvery()->days())
                ->setStart($chargeInformation->validFor()->days());


            if (isset($taxBreakDown['rebillAmount'])) {
                $rebillAmount = new EpochTransactionRequestBodyTaxInitialAmount($taxBreakDown['rebillAmount']);
                $taxInfo->setRebillAmount($rebillAmount);
            }

            $saleRequest->setRebill($rebill);
        }

        if (!empty($taxInfo->getInitialAmount())) {
            if (!is_null($taxInformation->taxApplicationId())) {
                $taxInfo->setTaxApplicationId($taxInformation->taxApplicationId());
            }
            if (!is_null($taxInformation->taxRate())) {
                $taxInfo->setTaxRate($taxInformation->taxRate()->value());
            }
            if ($taxInformation->taxType()->value() != TaxType::UNKNOWN) {
                $taxInfo->setTaxType((string) $taxInformation->taxType());
            }
            if (!is_null($taxInformation->taxName())) {
                $taxInfo->setTaxName($taxInformation->taxName());
            }
            $saleRequest->setTax($taxInfo);
        }
    }

    /**
     * @param UserInfo    $userInfo       User info.
     * @param string      $paymentType    Payment type.
     * @param string|null $paymentMethod  Payment method.
     * @param string|null $billerMemberId Biller member id.
     * @return EpochTransactionRequestBodyPayment
     */
    private function setPaymentInfo(
        UserInfo $userInfo,
        string $paymentType,
        ?string $paymentMethod = null,
        ?string $billerMemberId = null
    ): EpochTransactionRequestBodyPayment {
        $paymentInfo = new EpochTransactionRequestBodyPayment();

        $paymentInfo->setType($paymentType);
        $paymentMethod = empty($paymentMethod) ? null : $paymentMethod;
        $paymentInfo->setMethod($paymentMethod);

        $paymentData = new EpochTransactionRequestBodyPaymentInformation();

        $member = new EpochTransactionRequestBodyPaymentInformationMember();

        return $this->updatePaymentInfo($paymentInfo, $paymentData, $member, $userInfo, $billerMemberId);
    }

    /**
     * @param EpochTransactionRequestBodyPayment|QyssoTransactionRequestBodyPayment $paymentInfo
     * @param EpochTransactionRequestBodyPaymentInformation                         $paymentData
     * @param EpochTransactionRequestBodyPaymentInformationMember                   $member
     * @param UserInfo                                                              $userInfo
     * @param ?string $billerMemberId
     * @return EpochTransactionRequestBodyPayment|QyssoTransactionRequestBodyPayment
     */
    public function updatePaymentInfo($paymentInfo, $paymentData, $member, $userInfo, $billerMemberId)
    {
        $member->setUsername((string) $userInfo->username())
            ->setPassword((string) $userInfo->password());

        if (!is_null($userInfo->firstName())) {
            $member->setFirstName((string) $userInfo->firstName());
            $member->setLastName((string) $userInfo->lastName());
        }

        if (!is_null($userInfo->email())) {
            $member->setEmail((string) $userInfo->email());
        }

        if (!is_null($userInfo->countryCode())) {
            $member->setCountry((string) $userInfo->countryCode());
        }

        if (!is_null($userInfo->state())) {
            $member->setState((string) $userInfo->state());
        }

        if (!is_null($userInfo->zipCode())) {
            $member->setZipCode((string) $userInfo->zipCode());
        }

        if (!is_null($userInfo->city())) {
            $member->setCity((string) $userInfo->city());
        }

        if (!is_null($userInfo->address())) {
            $member->setAddress((string) $userInfo->address());
        }

        if (!is_null($userInfo->phoneNumber())) {
            $member->setPhone((string) $userInfo->phoneNumber());
        }

        $member->setMemberId($billerMemberId);
        $paymentData->setMember($member);
        $paymentInfo->setInformation($paymentData);

        return $paymentInfo;
    }

    /**
     * Map transaction query to qysso sale request
     *
     * @param string              $sessionId         Session id
     * @param Site                $site              Site.
     * @param Biller              $biller            Biller.
     * @param CurrencyCode        $currency          Currency.
     * @param UserInfo            $userInfo          User info.
     * @param ChargeInformation   $chargeInformation Product info.
     * @param PaymentInfo         $paymentInfo       Payment info.
     * @param BillerMapping       $billerMapping     Biller fields.
     * @param string              $redirectUrl       Redirect url.
     * @param TaxInformation|null $taxInformation    Tax information.
     * @param string|null         $paymentMethod     Payment method.
     * @param string|null         $billerMemberId    Biller member id.
     * @return QyssoTransactionRequestBody
     */
    private function getQyssoSaleRequest(
        string $sessionId,
        Site $site,
        Biller $biller,
        CurrencyCode $currency,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        string $redirectUrl,
        ?TaxInformation $taxInformation,
        ?string $paymentMethod,
        ?string $billerMemberId
    ): QyssoTransactionRequestBody {
        $saleRequest = new QyssoTransactionRequestBody();

        $notificationUrl = env('POSTBACK_SERVICE_HOST') .
                           '/api/v1/postback/' .
                           $billerMapping->billerFields()->postbackId()
                           . '/session/' . $sessionId;

        $clientBillerFields = (new QyssoTransactionRequestBodyBillerFields())
            ->setCompanyNum($billerMapping->billerFields()->companyNum())
            ->setPersonalHashKey($billerMapping->billerFields()->personalHashKey())
            ->setRedirectUrl($redirectUrl)
            ->setNotificationUrl($notificationUrl);

        $saleRequest->setSiteId((string) $site->id())
            ->setSiteName((string) $site->url())
            ->setBillerId($biller->id())
            ->setAmount($chargeInformation->initialAmount()->value())
            ->setCurrency($currency->getValue())
            ->setBillerFields($clientBillerFields)
            ->setClientIp($userInfo->ipAddress()->ip());

        $qyssoPaymentInfo = new QyssoTransactionRequestBodyPayment();

        $qyssoPaymentInfo->setType($paymentInfo->paymentType());
        $paymentMethod = empty($paymentMethod) ? null : $paymentMethod;
        $qyssoPaymentInfo->setMethod($paymentMethod);

        $paymentData = new EpochTransactionRequestBodyPaymentInformation();

        $member = new EpochTransactionRequestBodyPaymentInformationMember();

        $saleRequest->setPayment(
            $this->updatePaymentInfo($qyssoPaymentInfo, $paymentData, $member, $userInfo, $billerMemberId)
        );

        $this->updateSaleInformation($saleRequest, $chargeInformation, $taxInformation);

        return $saleRequest;
    }
}
