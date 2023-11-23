<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\CircuitBreaker\BadRequestException;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\GetTransactionDataByInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class CircuitBreakerGetTransactionDataByServiceAdapter extends CircuitBreaker implements GetTransactionDataByInterfaceAdapter
{
    /**
     * @var GetTransactionDataByAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerGetTransactionDataByServiceAdapter constructor.
     * @param CommandFactory              $commandFactory Command
     * @param GetTransactionDataByAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        GetTransactionDataByAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param TransactionId $transactionId Transaction Id
     * @param SessionId     $sessionId     Session Id
     * @return RetrieveTransactionResult
     * @throws Exception
     */
    public function getTransactionDataBy(TransactionId $transactionId, SessionId $sessionId): RetrieveTransactionResult
    {
        $command = $this->commandFactory->getCommand(
            RetrieveGetTransactionDataByCommand::class,
            $this->adapter,
            $transactionId,
            $sessionId
        );

        try {
            return $command->execute();
        } catch (BadRequestException $exception) {
            // This is the exception that was encapsulated in the bad request exception
            // to allow circuit breaker logic bypass
            throw $exception->getPrevious();
        } catch (RuntimeException $exception) {
            // This is the exception thrown by us based on the service call
            // Extracting it from the CB runtime exception and throwing it further
            if (!empty($exception->getFallbackException())) {
                throw $exception->getFallbackException();
            }

            throw $exception->getPrevious();
        }
    }
}
