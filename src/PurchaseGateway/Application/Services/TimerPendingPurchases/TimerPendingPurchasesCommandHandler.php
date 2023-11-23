<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Application\Service\BaseTrackingWorkerHandler;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;

class TimerPendingPurchasesCommandHandler extends BaseTrackingWorkerHandler
{
    public const WORKER_NAME = 'timer-pending-purchases';

    /**
     * @var SessionHandler
     */
    private $purchaseProcessHandler;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * TimerPendingPurchasesCommandHandler constructor.
     * @param Projectionist      $projectionist          Projectionist
     * @param ItemSourceBuilder  $itemSourceBuilder      Item source builder
     * @param SessionHandler     $purchaseProcessHandler Session handler
     * @param TransactionService $transactionService     Transaction service
     */
    public function __construct(
        Projectionist $projectionist,
        ItemSourceBuilder $itemSourceBuilder,
        SessionHandler $purchaseProcessHandler,
        TransactionService $transactionService
    ) {
        parent::__construct($projectionist, $itemSourceBuilder);

        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->transactionService     = $transactionService;
    }

    /**
     * @param ItemToWorkOn $item Item
     * @return void
     * @throws \Exception
     */
    public function operation(ItemToWorkOn $item): void
    {
        $sessionPayload    = json_decode($item->body(), true);
        $purchaseProcessed = PurchaseProcess::restore($sessionPayload);

        if ($purchaseProcessed->isPending()) {
            $purchaseProcessed->redirect();
        }

        if ($purchaseProcessed->isRedirected()) {
            $purchaseProcessed->finishProcessing();
        }

        /**
         * @var Transaction $mainTransaction
         */

        $mainTransaction = $purchaseProcessed->retrieveMainPurchaseItem()->lastTransaction();

        if (!is_null($mainTransaction)) {
            $this->handleTransaction($mainTransaction);

            $crossSalesTransactions = $purchaseProcessed->retrieveInitializedCrossSales();

            foreach ($crossSalesTransactions as $initializedItem) {
                $this->handleTransaction($initializedItem->lastTransaction());
            }
        }

        $this->purchaseProcessHandler->update($purchaseProcessed);
    }

    /**
     * @param Transaction|null $transaction Transaction
     * @throws \Exception
     * @return void
     */
    protected function handleTransaction(?Transaction $transaction): void
    {
        if (is_null($transaction)) {
            return;
        }
        if ($transaction->state() != Transaction::STATUS_PENDING) {
            return;
        }

        $transaction->setState(Transaction::STATUS_ABORTED);

        $this->transactionService->abortTransaction(
            $transaction->transactionId(),
            SessionId::createFromString(Log::getSessionId())
        );
    }
}
