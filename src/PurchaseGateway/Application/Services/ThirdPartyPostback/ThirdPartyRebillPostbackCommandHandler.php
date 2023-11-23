<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class ThirdPartyRebillPostbackCommandHandler extends BasePaymentProcessCommandHandler
{
    /**
     * @var ThirdPartyPostbackDTOAssembler
     */
    protected $assembler;

    /**
     * @var SessionHandler
     */
    protected $purchaseProcessHandler;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var PurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var PurchaseService
     */
    protected $purchaseService;

    /**
     * @var Purchase
     */
    protected $purchase;

    /**
     * @var BILoggerService
     */
    protected $biLoggerService;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * @var PostbackService
     */
    protected $postbackService;

    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var SodiumCryptService
     */
    protected $cryptService;


    /**
     * PostbackCommandHandler constructor.
     * @param ThirdPartyPostbackDTOAssembler $assembler              Assembler
     * @param SessionHandler                 $purchaseProcessHandler Session handler
     * @param TransactionService             $transactionService     Transaction service
     * @param PurchaseService                $purchaseService        Purchase service
     * @param BILoggerService                $biLoggerService        BI Logger service
     * @param ConfigService                  $configServiceClient    Config Service
     * @param PostbackService                $postbackService        Postback service
     * @param TokenGenerator                 $tokenGenerator         Token generator
     * @param CryptService                   $cryptService           Crypt service
     * @param EventIngestionService          $eventIngestionService  Event ingestion service
     */
    public function __construct(
        ThirdPartyPostbackDTOAssembler $assembler,
        SessionHandler $purchaseProcessHandler,
        TransactionService $transactionService,
        PurchaseService $purchaseService,
        BILoggerService $biLoggerService,
        ConfigService $configServiceClient,
        PostbackService $postbackService,
        TokenGenerator $tokenGenerator,
        CryptService $cryptService,
        EventIngestionService $eventIngestionService
    ) {
        $this->assembler              = $assembler;
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->transactionService     = $transactionService;
        $this->purchaseService        = $purchaseService;
        $this->biLoggerService        = $biLoggerService;
        $this->configServiceClient    = $configServiceClient;
        $this->postbackService        = $postbackService;
        $this->tokenGenerator         = $tokenGenerator;
        $this->cryptService           = $cryptService;
        $this->eventIngestionService  = $eventIngestionService;
    }

    /**
     * @param Command $command Command
     * @return mixed
     * @throws InvalidCommandException
     * @throws SessionNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof ThirdPartyPostbackCommand) {
            throw new InvalidCommandException(ThirdPartyPostbackCommand::class, $command);
        }

        try {
            // retrieve previous purchase process
            $previousPurchaseProcess = $this->purchaseProcessHandler->load((string) $command->sessionId());

            // check if previous session is processed
            $this->validatePreviousPurchaseProcess($previousPurchaseProcess->state());

            // we need to get the previous transaction id here
            // because in the next method we overwrite it
            $previousTransactionId = $previousPurchaseProcess->initializedItemCollection()
                ->retrieveMainItem()
                ->lastTransactionId();

            // create new purchase process based on the previous one
            $this->createNewPurchaseProcessBasedOnPrevious($previousPurchaseProcess);

            // adding biller interaction
            $response = $this->transactionService->createRebillTransaction(
                $previousTransactionId,
                (string) $this->purchaseProcess->cascade()->currentBiller(),
                $command->sessionId(),
                $command->payload()
            );

            // update transaction status from purchase process(session), payment type and payment method
            $this->purchaseProcess->addTransactionToItem(
                Transaction::create(
                    $response->transactionId(),
                    $response->state(),
                    (string) $this->purchaseProcess->cascade()->currentBiller()
                ),
                (string) $this->purchaseProcess->initializedItemCollection()->retrieveMainItem()->itemId()
            );

            // create and persist purchase entity
            $this->purchase = $this->purchaseService->createPurchaseEntityForThirdParty($this->purchaseProcess, true);

            $mainPurchase = $this->purchaseProcess->retrieveMainPurchaseItem();
            $site         = $this->configServiceClient->getSite((string) $mainPurchase->siteId());

            // Ship BI event
            $this->shipBiProcessedPurchaseEvent($site);

            // setting purchase process state to processed
            $this->purchaseProcess->redirect();
            $this->purchaseProcess->finishProcessing();

            return $this->assembler->assemble(
                (string) $this->purchaseProcess->sessionId(),
                $response->state()
            );
        } catch (InitPurchaseInfoNotFoundException $exception) {
            throw new SessionNotFoundException($exception);
        } finally {
            if (isset($this->purchaseProcess)) {
                $this->purchaseProcessHandler->create($this->purchaseProcess);
            }
        }
    }

    /**
     * @param AbstractState $state State
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidStateException
     */
    private function validatePreviousPurchaseProcess(AbstractState $state): void
    {
        if ($state instanceof Processed) {
            return;
        }

        throw new InvalidStateException();
    }

    /**
     * @param PurchaseProcess $previousPurchaseProcess Previous purchase process
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    private function createNewPurchaseProcessBasedOnPrevious(PurchaseProcess $previousPurchaseProcess): void
    {
        $initializedItemCollection = new InitializedItemCollection();

        /** @var InitializedItem $initializedItem */
        foreach ($previousPurchaseProcess->initializedItemCollection() as $initializedItem) {
            // reseting transaction collections because we will have new transactions for the new session
            $initializedItem->resetTransactionCollection();

            $initializedItemCollection->offsetSet(
                (string) $initializedItem->itemId(),
                $initializedItem
            );
        }

        // creating new purchase process based on the previous session
        $this->purchaseProcess = PurchaseProcess::create(
            SessionId::create(),
            $previousPurchaseProcess->atlasFields(),
            $previousPurchaseProcess->publicKeyIndex(),
            $previousPurchaseProcess->userInfo(),
            $previousPurchaseProcess->paymentInfo(),
            $initializedItemCollection,
            $previousPurchaseProcess->memberId(),
            $previousPurchaseProcess->entrySiteId(),
            $previousPurchaseProcess->currency(),
            $previousPurchaseProcess->redirectUrl(),
            $previousPurchaseProcess->postbackUrl(),
            $previousPurchaseProcess->trafficSource()
        );

        // setting cascade the same as in the previous session
        $this->purchaseProcess->setCascade($previousPurchaseProcess->cascade());

        // setting purchase process state to valid
        $this->purchaseProcess->validate();
    }

    /**
     * @return BaseEvent
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        return PurchaseBiEventFactory::createForNewCC($this->purchaseProcess);
    }
}
