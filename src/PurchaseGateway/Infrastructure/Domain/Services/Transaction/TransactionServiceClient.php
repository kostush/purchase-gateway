<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProbillerNG\TransactionServiceClient\Api\EpochApi;
use ProbillerNG\TransactionServiceClient\Api\NetbillingApi;
use ProbillerNG\TransactionServiceClient\Api\QyssoApi;
use ProbillerNG\TransactionServiceClient\Api\RocketgateApi;
use ProbillerNG\TransactionServiceClient\Api\TransactionApi;
use ProbillerNG\TransactionServiceClient\Model\AddBillerInteractionForQyssoRebillRequestBody;
use ProbillerNG\TransactionServiceClient\Model\CompleteSimplifiedThreeDRequestBody;
use ProbillerNG\TransactionServiceClient\Model\CompleteThreeDRequestBody;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBody;
use ProbillerNG\TransactionServiceClient\Model\ExistingCardSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\InlineObject5;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse200;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse400;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse4001;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse404;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse500;
use ProbillerNG\TransactionServiceClient\Model\LookupRequestBody;
use ProbillerNG\TransactionServiceClient\Model\NetbillingExistingCardSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\NetBillingSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\QyssoTransactionRequestBody;
use ProbillerNG\TransactionServiceClient\Model\OutputError;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\ApiException;
use ProbillerNG\TransactionServiceClient\Model\RocketgateOtherPaymentTypeSaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\SaleRequestBody;
use ProbillerNG\TransactionServiceClient\Model\Transaction;
use ProbillerNG\TransactionServiceClient\Model\AbortTransactionResponse;

class TransactionServiceClient
{
    /**
     * @var TransactionApi
     */
    private $transactionServiceApi;

    /**
     * @var NetbillingApi
     */
    private $netbillingTransactionApi;

    /**
     * @var EpochApi
     */
    private $epochTransactionApi;

    /**
     * @var QyssoApi
     */
    private $qyssoTransactionApi;

    /** @var RocketgateApi */
    private $rocketgateTransactionApi;

    /**
     * TransactionServiceClient constructor.
     *
     * @param TransactionApi $transactionServiceApi    TransactionApi
     * @param NetbillingApi  $netbillingTransactionApi NetbillingApi
     * @param EpochApi       $epochTransactionApi      EpochApi
     * @param QyssoApi       $qyssoTransactionApi      QyssoApi
     * @param RocketgateApi  $rocketgateTransactionApi RocketgateApi
     */
    public function __construct(
        TransactionApi $transactionServiceApi,
        NetbillingApi $netbillingTransactionApi,
        EpochApi $epochTransactionApi,
        QyssoApi $qyssoTransactionApi,
        RocketgateApi $rocketgateTransactionApi
    ) {
        $this->transactionServiceApi    = $transactionServiceApi;
        $this->netbillingTransactionApi = $netbillingTransactionApi;
        $this->epochTransactionApi      = $epochTransactionApi;
        $this->qyssoTransactionApi      = $qyssoTransactionApi;
        $this->rocketgateTransactionApi = $rocketgateTransactionApi;
    }

    /**
     * Performs transaction on Rocketgate
     *
     * @param SaleRequestBody $saleRequest Sale request body
     * @param string          $sessionId   Session Id
     *
     * @return Transaction|InlineResponse400
     * @throws ApiException
     */
    public function performRocketgateTransactionWithNewCard(SaleRequestBody $saleRequest, string $sessionId)
    {
        return $this->transactionServiceApi->createTransactionWithNewCard(
            RocketgateBiller::BILLER_NAME,
            $sessionId,
            $saleRequest
        );
    }

    /**
     * Performs transaction on Rocketgate
     *
     * @param NetBillingSaleRequestBody $saleRequest Sale request body
     * @param string                    $sessionId   Session Id
     *
     * @return Transaction|InlineResponse400
     * @throws ApiException
     */
    public function performNetbillingTransactionWithNewCard(
        NetBillingSaleRequestBody $saleRequest,
        string $sessionId
    ) {
        return $this->netbillingTransactionApi->createTransactionNetbillingWithNewCard(
            NetbillingBiller::BILLER_NAME,
            $sessionId,
            $saleRequest
        );
    }

    /**
     * Performs transaction on Rocketgate using card hash / existing card
     *
     * @param ExistingCardSaleRequestBody $saleRequest Existing cardSale request body
     * @param string                      $sessionId   Session Id
     *
     * @return Transaction|InlineResponse400
     * @throws ApiException
     */
    public function performRocketgateTransactionWithCardHash(
        ExistingCardSaleRequestBody $saleRequest,
        string $sessionId
    ) {
        return $this->transactionServiceApi->createTransactionWithExistingCard(
            RocketgateBiller::BILLER_NAME,
            $sessionId,
            $saleRequest
        );
    }

    /**
     * @param string $transactionId Transaction id
     * @param string $sessionId     Session id
     *
     * @return InlineResponse404|InlineResponse500|RetrieveTransaction
     * @throws ApiException
     */
    public function getTransactionDataBy(string $transactionId, string $sessionId)
    {
        return $this->transactionServiceApi->retrieveTransaction($transactionId, $sessionId);
    }

    /**
     * Performs transaction on Netbilling
     *
     * @param NetbillingExistingCardSaleRequestBody $saleRequest Sale request body
     * @param string                                $sessionId   Session Id
     *
     * @return Transaction|InlineResponse400
     * @throws ApiException
     */
    public function performNetbillingTransactionWithCardHash(
        NetbillingExistingCardSaleRequestBody $saleRequest,
        string $sessionId
    ) {
        return $this->netbillingTransactionApi->createTransactionNetbillingWithExistingCard(
            NetbillingBiller::BILLER_NAME,
            $sessionId,
            $saleRequest
        );
    }

    /**
     * @param string                    $transactionId       Transaction Id
     * @param CompleteThreeDRequestBody $completeRequestBody Complete ThreeD Request Body
     * @param string                    $sessionId           Session Id
     * @return InlineResponse200|InlineResponse4001
     * @throws ApiException
     */
    public function performCompleteThreeDTransaction(
        string $transactionId,
        CompleteThreeDRequestBody $completeRequestBody,
        string $sessionId
    ) {
        return $this->transactionServiceApi->completeThreeDTransaction(
            $transactionId,
            $sessionId,
            $completeRequestBody
        );
    }

    /**
     * @param string                              $transactionId                 Transaction Id
     * @param CompleteSimplifiedThreeDRequestBody $simplifiedCompleteRequestBody Complete ThreeD Request Body
     * @param string                              $sessionId                     Session Id
     * @return InlineResponse200|InlineResponse4001
     * @throws ApiException
     */
    public function performSimplifiedCompleteThreeDTransaction(
        string $transactionId,
        CompleteSimplifiedThreeDRequestBody $simplifiedCompleteRequestBody,
        string $sessionId
    ) {
        return $this->transactionServiceApi->completeSimplifiedThreeDTransaction(
            $transactionId,
            $sessionId,
            $simplifiedCompleteRequestBody
        );
    }

    /**
     * @param string        $transactionId Transaction id
     * @param string        $sessionId     Session id
     * @param InlineObject5 $returnPayload Return from Epoch payload
     *
     * @return mixed
     * @throws ApiException
     */
    public function addEpochBillerInteraction(
        string $transactionId,
        string $sessionId,
        InlineObject5 $returnPayload
    ) {
        return $this->epochTransactionApi->putApiV1TransactionTransactionIdEpochBillerInteraction(
            $transactionId,
            $sessionId,
            $returnPayload
        );
    }

    /**
     * @param string        $transactionId Transaction id
     * @param string        $sessionId     Session id
     * @param InlineObject5 $returnPayload Return from Epoch payload
     * @return mixed
     * @throws ApiException
     */
    public function addQyssoBillerInteraction(
        string $transactionId,
        string $sessionId,
        InlineObject5 $returnPayload
    ) {
        return $this->qyssoTransactionApi->putApiV1TransactionTransactionIdQyssoBillerInteraction(
            $transactionId,
            $sessionId,
            $returnPayload
        );
    }

    /**
     * @param AddBillerInteractionForQyssoRebillRequestBody $rebillPayload Rebill payload
     * @param string                                        $sessionId     Session id
     * @return mixed
     * @throws ApiException
     */
    public function performQyssoRebillTransaction(
        AddBillerInteractionForQyssoRebillRequestBody $rebillPayload,
        string $sessionId
    ) {
        return $this->qyssoTransactionApi->addBillerInteractionForQyssoRebill($rebillPayload, $sessionId);
    }

    /**
     * Performs transaction on Epoch
     *
     * @param EpochTransactionRequestBody $saleRequest Sale request body
     * @param string                      $sessionId   Session Id
     *
     * @return Transaction|InlineResponse400
     * @throws ApiException
     */
    public function performEpochTransaction(EpochTransactionRequestBody $saleRequest, string $sessionId)
    {
        return $this->epochTransactionApi->createTransactionWithEpoch(
            $saleRequest,
            $sessionId
        );
    }

    /**
     * Performs transaction on Epoch
     *
     * @param QyssoTransactionRequestBody $saleRequest Sale request body
     * @param string                      $sessionId   Session Id
     * @return Transaction|InlineResponse400
     * @throws ApiException
     */
    public function performQyssoTransaction(QyssoTransactionRequestBody $saleRequest, string $sessionId)
    {
        return $this->qyssoTransactionApi->createTransactionWithQysso(
            $saleRequest,
            $sessionId
        );
    }

    /**
     * Performs transaction on Epoch
     *
     * @param string $transactionId Transaction id
     * @param string $sessionId     Session id
     *
     * @return AbortTransactionResponse|InlineResponse404|OutputError
     * @throws ApiException
     */
    public function abortTransaction(string $transactionId, string $sessionId)
    {
        return $this->transactionServiceApi->abortTransaction(
            $transactionId,
            $sessionId
        );
    }

    /**
     * Performs transaction on Epoch
     *
     * @param LookupRequestBody $lookupRequestBody Lookup request
     * @param string            $billerName        Biller Name
     * @param string            $sessionId         Session id
     *
     * @return InlineResponse400|InlineResponse500|Transaction
     * @throws ApiException
     */
    public function lookupThreedsTransaction(
        LookupRequestBody $lookupRequestBody,
        string $billerName,
        string $sessionId
    ) {
        return $this->transactionServiceApi->threedsLookup(
            $billerName,
            $sessionId,
            $lookupRequestBody
        );
    }

    /**
     * @param RocketgateOtherPaymentTypeSaleRequestBody $saleRequest
     * @param string                                    $sessionId
     *
     * @return InlineResponse400|InlineResponse500|Transaction
     * @throws ApiException
     */
    public function performRocketgateTransactionWithNewCheque(
        RocketgateOtherPaymentTypeSaleRequestBody $saleRequest,
        string $sessionId
    ) {
        return $this->rocketgateTransactionApi->postApiV1SaleCheckRocketgateSessionSessionId(
            $sessionId,
            $saleRequest
        );
    }
}
