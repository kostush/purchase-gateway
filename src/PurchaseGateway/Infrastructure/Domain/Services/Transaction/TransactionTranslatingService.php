<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\AddEpochBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\AddQyssoBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\CompleteThreeDInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\ExistingCardPerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\NewCardPerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\NewChequePerformTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformAbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformLookupThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformQyssoRebillTransactionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformThirdPartyTransactionAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\SimplifiedCompleteThreeDInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionTranslatingService as TransactionTranslatingServiceInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\AbortTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyRebillTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;

class TransactionTranslatingService implements TransactionTranslatingServiceInterface
{
    /**
     * @var ExistingCardPerformTransactionInterfaceAdapter
     */
    private $existingCardPerformTransactionAdapter;

    /**
     * @var NewCardPerformTransactionInterfaceAdapter
     */
    private $newCardPerformTransactionAdapter;

    /**
     * @var GetTransactionDataByInterfaceAdapter
     */
    private $getTransactionDataByAdapter;

    /**
     * @var CompleteThreeDInterfaceAdapter
     */
    private $completeThreeDTransactionAdapter;

    /**
     * @var SimplifiedCompleteThreeDInterfaceAdapter
     */
    private $simplifiedCompleteThreeDTransactionAdapter;

    /**
     * @var AddEpochBillerInteractionInterfaceAdapter
     */
    private $addEpochBillerInteractionAdapter;

    /**
     * @var AddQyssoBillerInteractionInterfaceAdapter
     */
    private $addQyssoBillerInteractionAdapter;

    /**
     * @var PerformQyssoRebillTransactionInterfaceAdapter
     */
    private $performQyssoRebillTransactionAdapter;

    /**
     * @var PerformThirdPartyTransactionAdapter
     */
    private $thirdPartyTransactionAdapter;

    /**
     * @var PerformAbortTransactionAdapter
     */
    private $abortTransactionAdapter;

    /**
     * @var PerformLookupThreeDTransactionAdapter
     */
    private $lookupTransactionAdapter;

    /**
     * @var NewChequePerformTransactionInterfaceAdapter
     */
    private $newChequeTransactionAdapter;

    /**
     * TransactionTranslatingService constructor.
     *
     * @param ExistingCardPerformTransactionInterfaceAdapter $existingCardPerformTransactionAdapter ExistingCardAdapter
     * @param NewCardPerformTransactionInterfaceAdapter      $newCardPerformTransactionAdapter      NewCardAdapter
     * @param GetTransactionDataByInterfaceAdapter           $getTransactionDataByAdapter           GetDataAdapter
     * @param CompleteThreeDInterfaceAdapter                 $completeThreeDTransactionAdapter      CompleteAdapter
     * @param SimplifiedCompleteThreeDInterfaceAdapter       $simplifiedCompleteThreeDInterfaceAdapter
     * @param AddEpochBillerInteractionInterfaceAdapter      $addEpochBillerInteractionAdapter      BInteractionAdapter
     * @param AddQyssoBillerInteractionInterfaceAdapter      $addQyssoBillerInteractionAdapter      BInteractionAdapter
     * @param PerformQyssoRebillTransactionInterfaceAdapter  $performQyssoRebillTransactionAdapter  Qysso rebill adapter
     * @param PerformThirdPartyTransactionAdapter            $thirdPartyTransactionAdapter          ThirdPartyAdapter
     * @param PerformAbortTransactionAdapter                 $abortTransactionAdapter               AbortTransaction
     * @param PerformLookupThreeDTransactionAdapter          $lookupTransactionAdapter              Lookup adapter
     * @param NewChequePerformTransactionInterfaceAdapter    $newChequeTransactionAdapter
     */
    public function __construct(
        ExistingCardPerformTransactionInterfaceAdapter $existingCardPerformTransactionAdapter,
        NewCardPerformTransactionInterfaceAdapter $newCardPerformTransactionAdapter,
        GetTransactionDataByInterfaceAdapter $getTransactionDataByAdapter,
        CompleteThreeDInterfaceAdapter $completeThreeDTransactionAdapter,
        SimplifiedCompleteThreeDInterfaceAdapter $simplifiedCompleteThreeDInterfaceAdapter,
        AddEpochBillerInteractionInterfaceAdapter $addEpochBillerInteractionAdapter,
        AddQyssoBillerInteractionInterfaceAdapter $addQyssoBillerInteractionAdapter,
        PerformQyssoRebillTransactionInterfaceAdapter $performQyssoRebillTransactionAdapter,
        PerformThirdPartyTransactionAdapter $thirdPartyTransactionAdapter,
        PerformAbortTransactionAdapter $abortTransactionAdapter,
        PerformLookupThreeDTransactionAdapter $lookupTransactionAdapter,
        NewChequePerformTransactionInterfaceAdapter $newChequeTransactionAdapter
    ) {
        $this->existingCardPerformTransactionAdapter      = $existingCardPerformTransactionAdapter;
        $this->newCardPerformTransactionAdapter           = $newCardPerformTransactionAdapter;
        $this->getTransactionDataByAdapter                = $getTransactionDataByAdapter;
        $this->completeThreeDTransactionAdapter           = $completeThreeDTransactionAdapter;
        $this->simplifiedCompleteThreeDTransactionAdapter = $simplifiedCompleteThreeDInterfaceAdapter;
        $this->addEpochBillerInteractionAdapter           = $addEpochBillerInteractionAdapter;
        $this->addQyssoBillerInteractionAdapter           = $addQyssoBillerInteractionAdapter;
        $this->performQyssoRebillTransactionAdapter       = $performQyssoRebillTransactionAdapter;
        $this->thirdPartyTransactionAdapter               = $thirdPartyTransactionAdapter;
        $this->abortTransactionAdapter                    = $abortTransactionAdapter;
        $this->lookupTransactionAdapter                   = $lookupTransactionAdapter;
        $this->newChequeTransactionAdapter                = $newChequeTransactionAdapter;
    }

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
     * @throws Exception
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
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
    ): Transaction {
        Log::info('Performing transaction with new credit card');

        return $this->newCardPerformTransactionAdapter->performTransaction(
            $siteId,
            $biller,
            $currencyCode,
            $userInfo,
            $chargeInformation,
            $paymentInfo,
            $billerMapping,
            $sessionId,
            $binRouting,
            $useThreeD,
            $returnUrl,
            $isNSFSupported
        );
    }

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
     * @throws LoggerException
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
    ): Transaction {
        Log::info('Performing transaction with an existing credit card');

        return $this->existingCardPerformTransactionAdapter->performTransaction(
            $siteId,
            $biller,
            $currencyCode,
            $userInfo,
            $chargeInformation,
            $paymentInfo,
            $billerMapping,
            $sessionId,
            $binRouting,
            $useThreeDS,
            $returnUrl
        );
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return RetrieveTransactionResult
     * @throws \Exception
     */
    public function getTransactionDataBy(TransactionId $transactionId, SessionId $sessionId): RetrieveTransactionResult
    {
        Log::info(
            'Retrieving transaction data by transactionId and sessionId',
            [
                'transactionId' => (string) $transactionId->value()
            ]
        );

        return $this->getTransactionDataByAdapter->getTransactionDataBy($transactionId, $sessionId);
    }

    /**
     * @param TransactionId $transactionId Transaction id.
     * @param string|null   $pares         Pares.
     * @param string|null   $md            Rocketgate biller transaction id.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     * @throws Exception
     */
    public function performCompleteThreeDTransaction(
        TransactionId $transactionId,
        ?string $pares,
        ?string $md,
        SessionId $sessionId
    ): Transaction {
        Log::info(
            'Performing complete threeD transaction',
            [
                'transactionId' => (string) $transactionId,
                'pares'         => (string) $pares,
                'md'            => (string) $md
            ]
        );

        return $this->completeThreeDTransactionAdapter->performCompleteThreeDTransaction(
            $transactionId,
            $pares,
            $md,
            $sessionId
        );
    }

    /**
     * @param TransactionId $transactionId Transaction Id.
     * @param string        $queryString   Query string.
     * @param SessionId     $sessionId     Session id.
     * @return Transaction
     * @throws LoggerException
     */
    public function performSimplifiedCompleteThreeDTransaction(
        TransactionId $transactionId,
        string $queryString,
        SessionId $sessionId
    ): Transaction {
        Log::info(
            'Performing the simplified complete threeD transaction',
            [
                'transactionId' => (string) $transactionId,
                'queryString'   => $queryString
            ]
        );

        return $this->simplifiedCompleteThreeDTransactionAdapter->performSimplifiedCompleteThreeDTransaction(
            $transactionId,
            $queryString,
            $sessionId
        );
    }

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
    ): EpochBillerInteraction {
        return $this->addEpochBillerInteractionAdapter->performAddEpochBillerInteraction(
            $transactionId,
            $sessionId,
            $returnPayload
        );
    }

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
    ): QyssoBillerInteraction {
        return $this->addQyssoBillerInteractionAdapter->performAddQyssoBillerInteraction(
            $transactionId,
            $sessionId,
            $returnPayload
        );
    }

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
    ): ThirdPartyRebillTransaction {
        return $this->performQyssoRebillTransactionAdapter->performQyssoRebillTransaction(
            $previousTransactionId,
            $sessionId,
            $rebillPayload
        );
    }

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
     * @throws Exception
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
    ): ThirdPartyTransaction {
        Log::info('Performing transaction with third party biller ' . $biller->name());

        return $this->thirdPartyTransactionAdapter->performTransaction(
            $site,
            $crossSaleSites,
            $biller,
            $currencyCode,
            $userInfo,
            $chargeInformation,
            $paymentInfo,
            $billerMapping,
            $sessionId,
            $redirectUrl,
            $taxInformation,
            $crossSales,
            $paymentMethod,
            $billerMemberId
        );
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @param SessionId     $sessionId     Session id
     * @return AbortTransactionResult
     * @throws Exception
     */
    public function abortTransaction(TransactionId $transactionId, SessionId $sessionId): AbortTransactionResult
    {
        Log::info(
            'Abort transaction: ',
            [
                'transactionId' => (string) $transactionId->value()
            ]
        );

        return $this->abortTransactionAdapter->abortTransaction($transactionId, $sessionId);
    }

    /**
     * @param TransactionId $transactionId       Transaction id
     * @param PaymentInfo   $paymentInfo         The payment info
     * @param string        $redirectUrl         Redirect url
     * @param string        $deviceFingerprintId Device fingerprint Id
     * @param string        $billerName          Biller Name
     * @param SessionId     $sessionId           The session id
     * @param bool          $isNsfSupported      Is NSF supported
     * @return Transaction
     * @throws LoggerException
     */
    public function performLookupTransaction(
        TransactionId $transactionId,
        PaymentInfo $paymentInfo,
        string $redirectUrl,
        string $deviceFingerprintId,
        string $billerName,
        SessionId $sessionId,
        bool $isNsfSupported = false
    ): Transaction {
        Log::info('Performing lookup transaction for 3DS2.x');

        return $this->lookupTransactionAdapter->lookupTransaction(
            $transactionId,
            $paymentInfo,
            $redirectUrl,
            $deviceFingerprintId,
            $billerName,
            $sessionId,
            $isNsfSupported
        );
    }

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
     * @throws LoggerException
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
    ): Transaction {
        Log::info('Performing transaction with new cheque');

        return $this->newChequeTransactionAdapter->performTransaction(
            $siteId,
            $biller,
            $currencyCode,
            $userInfo,
            $chargeInformation,
            $paymentInfo,
            $billerMapping,
            $sessionId
        );
    }
}
