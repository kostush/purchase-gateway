<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction;

use ProBillerNG\Base\Application\Services\Query;
use ProBillerNG\Base\Application\Services\QueryHandler;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\BillerTransactionDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ItemNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class RetrieveBillerTransactionQueryHandler implements QueryHandler
{
    /**
     * @var ItemRepositoryReadOnly
     */
    private $itemRepository;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var BillerTransactionDTOAssembler
     */
    protected $dtoAssembler;

    /**
     * RetrieveBillerTransactionQueryHandler constructor.
     * @param ItemRepositoryReadOnly        $itemRepository     Item repository
     * @param TransactionService            $transactionService Transaction service
     * @param BillerTransactionDTOAssembler $dtoAssembler       Biller Transaction DTO Assembler
     */
    public function __construct(
        ItemRepositoryReadOnly $itemRepository,
        TransactionService $transactionService,
        BillerTransactionDTOAssembler $dtoAssembler
    ) {
        $this->itemRepository     = $itemRepository;
        $this->transactionService = $transactionService;
        $this->dtoAssembler       = $dtoAssembler;
    }

    /**
     * @param Query $query Query
     * @return mixed
     * @throws Exception
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute(Query $query)
    {
        try {
            // start validation section
            if (!$query instanceof RetrieveItemQuery) {
                throw new InvalidQueryException(RetrieveItemQuery::class, $query);
            }

            $itemId = ItemId::createFromString($query->itemId());
            $item   = $this->itemRepository->findById((string) $itemId);

            if (!$item instanceof ProcessedBundleItem) {
                $transactionId = $query->itemId();
            } else {
                $transactionId = $item->retrieveTransactionId();
            }

            $transaction = $this->retrieveTransactionData((string) $transactionId, $query->sessionId());

            return $this->dtoAssembler->assemble($transaction);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidUUIDException($e);
        }
    }

    /**
     * @param string $transactionId Transaction Id
     * @param string $sessionId     Session Id
     * @return RetrieveTransactionResult
     * @throws \Exception
     */
    protected function retrieveTransactionData(string $transactionId, string $sessionId): RetrieveTransactionResult
    {
        return $this->transactionService->getTransactionDataBy(
            TransactionId::createFromString($transactionId),
            SessionId::createFromString($sessionId)
        );
    }
}
