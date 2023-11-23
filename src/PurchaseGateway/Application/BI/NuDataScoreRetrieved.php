<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Illuminate\Contracts\Support\Arrayable;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;

class NuDataScoreRetrieved extends BaseEvent implements Arrayable
{
    const TYPE = 'NuData_Score_Retrieved';

    const LATEST_VERSION = 1;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * @var string
     */
    private $traceId;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $purchaseId;

    /**
     * @var InitializedItem
     */
    private $mainPurchaseItem;

    /**
     * @var array
     */
    private $processedCrossSales;

    /**
     * @var string
     */
    private $businessGroupId;

    /**
     * @var string
     */
    private $score;

    /**
     * NuDataScoreRetrieved constructor.
     * @param string          $traceId             Trace Id
     * @param string          $correlationId       Correlation Id
     * @param string          $sessionId           Session Id
     * @param Purchase|null   $purchase            Purchase
     * @param InitializedItem $mainPurchaseItem    Main Purchase Item
     * @param array           $processedCrossSales Processed Cross Sales
     * @param string          $businessGroupId     Business Group Id
     * @param string          $score               NuData Score
     * @throws \Exception
     */
    public function __construct(
        string $traceId,
        string $correlationId,
        string $sessionId,
        ?Purchase $purchase,
        InitializedItem $mainPurchaseItem,
        array $processedCrossSales,
        string $businessGroupId,
        string $score
    ) {
        parent::__construct(self::TYPE);

        // this is needed when we don't have a transaction (success = false)
        $purchaseId = (string) PurchaseId::create();

        $this->timestamp           = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->traceId             = $traceId;
        $this->correlationId       = $correlationId;
        $this->sessionId           = $sessionId;
        $this->purchaseId          = $purchase ? (string) $purchase->purchaseId() : $purchaseId;
        $this->mainPurchaseItem    = $mainPurchaseItem;
        $this->processedCrossSales = $processedCrossSales;
        $this->businessGroupId     = $businessGroupId;
        $this->score               = $this->prepareScore($score);

        $this->setValue($this->toArray());
    }

    /**
     * @param InitializedItem $purchaseItem Initialized Item
     * @return array
     */
    private function getItemAttemptedTransactions(InitializedItem $purchaseItem): array
    {
        /** @var  TransactionCollection $transactionCollection */
        $transactionCollection = $purchaseItem->transactionCollection();
        $attemptedTransactions = [];
        foreach ($transactionCollection as $transaction) {
            $attemptedTransactions[] = (string) $transaction->transactionId();
        }

        return $attemptedTransactions;
    }

    /**
     * @param InitializedItem[] $crossSales Cross Sales
     * @return array
     */
    private function getCrossSalesAttemptedTransactions(array $crossSales): array
    {
        $attemptedTransactions = [];
        foreach ($crossSales as $crossSale) {
            $attemptedTransactions[] = $this->getItemAttemptedTransactions($crossSale);
        }

        return $attemptedTransactions;
    }

    /**
     * @return array
     */
    private function getAttemptedTransactions(): array
    {
        $attemptedTransactions['mainItem']       = $this->getItemAttemptedTransactions($this->mainPurchaseItem);
        $attemptedTransactions['crossSaleItems'] = $this->getCrossSalesAttemptedTransactions($this->processedCrossSales);

        return $attemptedTransactions;
    }

    /**
     * @param string $score Score
     *
     * @return array|string
     */
    private function prepareScore(string $score)
    {
        // Check if we have a JSON and decode it so we avoid double encoding
        // This check is needed as the string might be an error coming from nuDetect, which is not in JSON format
        if ($decodedScore = json_decode($score, true)) {
            $score = $decodedScore;
        }

        return $score;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'                  => self::TYPE,
            'version'               => self::LATEST_VERSION,
            'timestamp'             => $this->timestamp,
            'traceId'               => $this->traceId,
            'correlationId'         => $this->correlationId,
            'sessionId'             => $this->sessionId,
            'purchaseId'            => $this->purchaseId,
            'attemptedTransactions' => $this->getAttemptedTransactions(),
            'businessGroupId'       => $this->businessGroupId,
            'score'                 => $this->score,
        ];
    }
}
