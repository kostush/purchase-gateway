<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers;

use ProBillerNG\Base\Application\Services\Query;
use ProBillerNG\Base\Application\Services\QueryHandler;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers\FailedBillersHttpQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use Ramsey\Uuid\Exception\InvalidUuidStringException;

class RetrieveFailedBillersQueryHandler implements QueryHandler
{
    /**
     * @var PurchaseProcessHandler
     */
    private $purchaseProcessHandler;

    /**
     * @var FailedBillersHttpQueryDTOAssembler
     */
    protected $dtoAssembler;

    /**
     * RetrieveFailedBillersQueryHandler constructor.
     * @param PurchaseProcessHandler             $purchaseProcessHandler The session handler
     * @param FailedBillersHttpQueryDTOAssembler $dtoAssembler           The DTO assembler
     */
    public function __construct(
        PurchaseProcessHandler $purchaseProcessHandler,
        FailedBillersHttpQueryDTOAssembler $dtoAssembler

    ) {
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->dtoAssembler           = $dtoAssembler;
    }

    /**
     * @param Query $query The retrieve failed billers query
     * @return array|mixed
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function execute(Query $query)
    {
        try {
            if (!$query instanceof RetrieveFailedBillersQuery) {
                throw new InvalidQueryException(RetrieveFailedBillersQuery::class, $query);
            }

            /** @var PurchaseProcess $purchaseProcess */
            $purchaseProcess = $this->purchaseProcessHandler->load($query->sessionId());

            $failedBillers = $this->getFailedBillers($purchaseProcess->initializedItemCollection());

            return $this->dtoAssembler->assemble(
                $failedBillers,
                $this->checkIfThreeDWasUsed($purchaseProcess)
            );
        } catch (InvalidUuidStringException $e) {
            throw new InvalidUUIDException($e);
        }
    }

    /**
     * @param InitializedItemCollection $initializedItemCollection The initialized item collection
     * @return FailedBillers
     * @throws \Exception
     */
    protected function getFailedBillers(InitializedItemCollection $initializedItemCollection): FailedBillers
    {
        return FailedBillers::createFromInitializedItemCollection($initializedItemCollection);
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return bool
     */
    protected function checkIfThreeDWasUsed(PurchaseProcess $purchaseProcess): bool
    {
        $threeDRequired = false;

        if ($purchaseProcess->fraudAdvice()) {
            $threeDRequired = $purchaseProcess->fraudAdvice()->isForceThreeD();
        }

        return $threeDRequired;
    }
}
