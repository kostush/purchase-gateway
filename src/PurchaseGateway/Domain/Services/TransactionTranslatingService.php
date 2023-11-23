<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;

interface TransactionTranslatingService
{
    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return RetrieveTransactionResult
     */
    public function getTransactionDataBy(TransactionId $transactionId, SessionId $sessionId): RetrieveTransactionResult;

    /**
     * @param SiteId            $siteId            The site id.
     * @param Biller            $biller            The biller id.
     * @param CurrencyCode      $currencyCode      The currency code.
     * @param UserInfo          $userInfo          The user info.
     * @param ChargeInformation $chargeInformation The charge information.
     * @param PaymentInfo       $paymentInfo       The payment info.
     * @param BillerMapping     $billerMapping     The biller mapping.
     * @param SessionId         $sessionId         The session id.
     * @param BinRouting|null   $binRouting        The bin routing.
     * @param bool              $useThreeD         Perform transaction using 3DS.
     * @param string|null       $returnUrl         The return URL for 3ds flow
     * @param bool              $isNSFSupported    Flag to show if NSF is supported or not
     *
     * @return Transaction
     */
    public function performTransactionWithNewCard(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        ?BinRouting $binRouting,
        bool $useThreeD,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): Transaction;

    /**
     * @param SiteId            $siteId            The site id.
     * @param Biller            $biller            The biller id.
     * @param CurrencyCode      $currencyCode      The currency code.
     * @param UserInfo          $userInfo          The user info.
     * @param ChargeInformation $chargeInformation The charge information.
     * @param PaymentInfo       $paymentInfo       The payment info.
     * @param BillerMapping     $billerMapping     The biller mapping.
     * @param SessionId         $sessionId         The session id.
     * @param BinRouting|null   $binRouting        The bin routing.
     * @param null|bool         $useThreeDS        The 3ds flag
     * @param null|string       $returnUrl         The return url for the 3ds flow
     * @return Transaction
     */
    public function performTransactionWithExistingCard(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        ?BinRouting $binRouting,
        ?bool $useThreeDS = false,
        ?string $returnUrl = null
    ): Transaction;

    /**
     * @param TransactionId $transactionId Transaction Id.
     * @param string|null   $pares         Pares.
     * @param string|null   $md            Rocketgate biller transaction id.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     */
    public function performCompleteThreeDTransaction(
        TransactionId $transactionId,
        ?string $pares,
        ?string $md,
        SessionId $sessionId
    ): Transaction;

    /**
     * @param TransactionId $transactionId Transaction Id.
     * @param string        $queryString   Query string.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     */
    public function performSimplifiedCompleteThreeDTransaction(
        TransactionId $transactionId,
        string $queryString,
        SessionId $sessionId
    ): Transaction;

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @param array         $returnPayload Return from Epoch payload
     * @return EpochBillerInteraction
     */
    public function addEpochBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): EpochBillerInteraction;

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @param array         $returnPayload Return from Epoch payload
     * @return QyssoBillerInteraction
     */
    public function addQyssoBillerInteraction(
        TransactionId $transactionId,
        SessionId $sessionId,
        array $returnPayload
    ): QyssoBillerInteraction;

    /**
     * @param TransactionId $previousTransactionId Previous transaction id
     * @param SessionId     $sessionId             Session id
     * @param array         $rebillPayload         Rebill payload
     * @return ThirdPartyRebillTransaction
     */
    public function createQyssoRebillTransaction(
        TransactionId $previousTransactionId,
        SessionId $sessionId,
        array $rebillPayload
    ): ThirdPartyRebillTransaction;

    /**
     * @param Site                $site              The site id.
     * @param array               $crossSaleSites    Cross sales sites.
     * @param Biller              $biller            The biller id.
     * @param CurrencyCode        $currencyCode      The currency code.
     * @param UserInfo            $userInfo          The user info.
     * @param ChargeInformation   $chargeInformation The charge information.
     * @param PaymentInfo         $paymentInfo       The payment info.
     * @param BillerMapping       $billerMapping     The biller mapping.
     * @param SessionId           $sessionId         The session id.
     * @param string              $redirectUrl       Redirect url.
     * @param TaxInformation|null $taxInformation    Tax information.
     * @param array|null          $crossSales        Cross sales list.
     * @param string|null         $paymentMethod     Payment method.
     * @param string|null         $billerMemberId    Biller member id.
     * @return ThirdPartyTransaction
     */
    public function performTransactionWithThirdParty(
        Site $site,
        array $crossSaleSites,
        Biller $biller,
        CurrencyCode $currencyCode,
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
    ): ThirdPartyTransaction;

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return AbortTransactionResult
     */
    public function abortTransaction(TransactionId $transactionId, SessionId $sessionId): AbortTransactionResult;

    /**
     * @param TransactionId $transactionId       Transaction id
     * @param PaymentInfo   $paymentInfo         The payment info
     * @param string        $redirectUrl         Redirect url
     * @param string        $deviceFingerprintId Device fingerprint Id
     * @param string        $billerName          Biller Name
     * @param SessionId     $sessionId           The session id
     * @param bool          $isNsfSupported      Is NSF supported on the item
     * @return Transaction
     */
    public function performLookupTransaction(
        TransactionId $transactionId,
        PaymentInfo $paymentInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        string $billerName,
        SessionId $sessionId,
        bool $isNsfSupported = false
    ): Transaction;

    /**
     * @param SiteId            $siteId            The site id.
     * @param Biller            $biller            The biller id.
     * @param CurrencyCode      $currencyCode      The currency code.
     * @param UserInfo          $userInfo          The user info.
     * @param ChargeInformation $chargeInformation The charge information.
     * @param PaymentInfo       $paymentInfo       The payment info.
     * @param BillerMapping     $billerMapping     The biller mapping.
     * @param SessionId         $sessionId         The session id.
     * @return Transaction
     */
    public function performTransactionWithCheque(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId
    ): Transaction;
}
