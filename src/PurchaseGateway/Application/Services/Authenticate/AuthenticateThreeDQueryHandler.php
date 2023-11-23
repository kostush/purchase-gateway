<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Authenticate;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseRedirectedTo3DAuthentication;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidQueryException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class AuthenticateThreeDQueryHandler
{
    /**
     * @var BILoggerService
     */
    private $biLoggerService;

    /**
     * @var SessionHandler
     */
    private $purchaseProcessHandler;

    /**
     * @var AuthenticateThreeDDTOAssembler
     */
    private $assembler;

    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var EventIngestionService
     */
    private $eventIngestionService;

    /**
     * AuthenticateQueryHandler constructor.
     * @param AuthenticateThreeDDTOAssembler $assembler              Authenticate DTO Assembler.
     * @param SessionHandler                 $purchaseProcessHandler Session Handler
     * @param BILoggerService                $biLoggerService        Bi logger service
     * @param EventIngestionService          $eventIngestionService
     */
    public function __construct(
        AuthenticateThreeDDTOAssembler $assembler,
        SessionHandler $purchaseProcessHandler,
        BILoggerService $biLoggerService,
        EventIngestionService $eventIngestionService
    ) {
        $this->assembler              = $assembler;
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->biLoggerService        = $biLoggerService;
        $this->eventIngestionService  = $eventIngestionService;
    }

    /**
     * @param AuthenticateThreeDQuery $query Query
     * @return mixed
     * @throws \Exception
     */
    public function execute(AuthenticateThreeDQuery $query): AuthenticateThreeDHttpDTO
    {
        if (!$query instanceof AuthenticateThreeDQuery) {
            throw new InvalidQueryException(AuthenticateThreeDQuery::class, $query);
        }

        try {
            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $query->sessionId());

            if (empty($this->purchaseProcess->redirectUrl())) {
                throw new MissingRedirectUrlException(
                    RestartProcess::create()->toArray()
                );
            }

            if (!$this->purchaseProcess->isPending()) {
                throw new SessionAlreadyProcessedException(
                    (string) $query->sessionId(),
                    RestartProcess::create()->toArray(),
                    $this->purchaseProcess->redirectUrl()
                );
            }

            $this->purchaseProcess->authenticateThreeD();
            $this->purchaseProcessHandler->update($this->purchaseProcess);

            $authenticatedBiEvent = new PurchaseRedirectedTo3DAuthentication(
                (string) $query->sessionId(),
                (string) $this->purchaseProcess->state()
            );

            $this->biLoggerService->write($authenticatedBiEvent);
            if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
                $this->eventIngestionService->queue($authenticatedBiEvent);
            }
        } catch (InitPurchaseInfoNotFoundException $ex) {
            throw new SessionNotFoundException($ex);
        }

        /**
         * @var Transaction $transaction
         */
        $transaction = $this->purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->last();

        return $this->assembler->assemble(
            $transaction->acs(),
            $transaction->pareq(),
            (string) $query->sessionId()
        );
    }
}
