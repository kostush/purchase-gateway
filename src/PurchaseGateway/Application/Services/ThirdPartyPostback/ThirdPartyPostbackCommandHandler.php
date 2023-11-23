<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException as TransactionAlreadyProcessed;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Domain\Services\UserInfoService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\FailedDependencyException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\MalformedPayloadException;

class ThirdPartyPostbackCommandHandler extends BasePaymentProcessCommandHandler
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
     * @var UserInfoService
     */
    private $userInfoService;

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
     * @param UserInfoService                $userInfoService        User info service
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
        UserInfoService $userInfoService,
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
        $this->userInfoService        = $userInfoService;
        $this->biLoggerService        = $biLoggerService;
        $this->configServiceClient    = $configServiceClient;
        $this->postbackService        = $postbackService;
        $this->tokenGenerator         = $tokenGenerator;
        $this->cryptService           = $cryptService;
        $this->eventIngestionService  = $eventIngestionService;
    }

    /**
     * Executes a command
     *
     * @param Command $command Command
     * @return mixed
     * @throws SessionNotFoundException
     * @throws Exception
     * @throws InvalidCommandException
     * @throws \Exception
     */
    public function execute(Command $command)
    {
        if (!$command instanceof ThirdPartyPostbackCommand) {
            throw new InvalidCommandException(ThirdPartyPostbackCommand::class, $command);
        }

        try {
            // retrieve purchase process
            $this->purchaseProcess = $this->purchaseProcessHandler->load((string) $command->sessionId());

            // check if transaction exist
            $this->checkTransaction($command->transactionId());

            // retrieve transaction from Transaction Service
            $transaction = $this->transactionService->getTransactionDataBy(
                TransactionId::createFromString($command->transactionId()),
                $command->sessionId()
            );

            // check if retrieved transaction has pending status
            if ($transaction->transactionInformation()->status() === Transaction::STATUS_PENDING) {
                // adding biller interaction
                $response = $this->transactionService->addBillerInteraction(
                    TransactionId::createFromString($transaction->transactionInformation()->transactionId()),
                    $transaction->billerName(),
                    $command->sessionId(),
                    $command->payload()
                );

                // update transaction status from purchase process(session), payment type and payment method
                $this->purchaseProcess->returnFromThirdPartyUpdates(
                    $response->transactionId(),
                    $response->status(),
                    $response->paymentType(),
                    $response->paymentMethod()
                );

                // user info service
                $this->userInfoService->update($this->purchaseProcess, $command->payload());

                // create and persist purchase entity
                $this->purchase = $this->purchaseService->createPurchaseEntityForThirdParty($this->purchaseProcess);

                $mainPurchase = $this->purchaseProcess->retrieveMainPurchaseItem();
                $site         = $this->configServiceClient->getSite((string) $mainPurchase->siteId());
                // Ship BI event
                $this->shipBiProcessedPurchaseEvent($site);

                // send postback to P1
                if ($this->purchaseProcess->retrieveMainPurchaseItem()->lastTransactionState() === Transaction::STATUS_APPROVED) {
                    $processPurchaseGeneralHttpDTO = new ProcessPurchaseGeneralHttpDTO(
                        $this->purchaseProcess,
                        $this->tokenGenerator,
                        $site,
                        $this->cryptService
                    );

                    $this->postbackService->queue(
                        $this->buildDtoPostback($processPurchaseGeneralHttpDTO, $this->purchaseProcess->userInfo()),
                        $this->getPostbackUrl($site)
                    );
                }
            }

            return $this->assembler->assemble(
                (string) $this->purchaseProcess->sessionId(),
                isset($response) ? $response->status() : $transaction->transactionInformation()->status()
            );
        } catch (InitPurchaseInfoNotFoundException $exception) {
            throw new SessionNotFoundException($exception);
        } catch (TransactionAlreadyProcessedException $ex) {
            $redirectUrl = $this->purchaseProcess->redirectUrl();
            throw new TransactionAlreadyProcessed(
                RestartProcess::create()->toArray(),
                $redirectUrl ?? ""
            );
        } catch (MalformedPayloadException $exception) {
            throw new FailedDependencyException($exception->serviceName);
        } finally {
            if (isset($this->purchaseProcess)) {
                $this->purchaseProcessHandler->update($this->purchaseProcess);
            }
        }
    }

    /**
     * @param string $transactionId Transaction id
     * @return void
     * @throws TransactionNotFoundException
     * @throws Exception
     */
    private function checkTransaction(string $transactionId): void
    {
        // check if transaction id from received payload exists for main purchase or cross sales
        if (!$this->purchaseProcess->checkIfTransactionIdExist($transactionId)) {
            throw new TransactionNotFoundException($transactionId);
        }
    }

    /**
     * @param ProcessPurchaseGeneralHttpDTO $dto      Process Purchase General Http DTO
     * @param UserInfo|null                 $userInfo User info
     * @return PostbackResponseDto
     */
    protected function buildDtoPostback(
        ProcessPurchaseGeneralHttpDTO $dto,
        ?UserInfo $userInfo = null
    ): PostbackResponseDto {

        return PostbackResponseDto::createFromResponseData(
            $dto,
            $dto->tokenGenerator(),
            $this->purchaseProcess->publicKeyIndex(),
            $this->purchaseProcess->sessionId(),
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveProcessedCrossSales(),
            $userInfo
        );
    }

    /**
     * @return BaseEvent
     * @throws \Exception
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        return PurchaseBiEventFactory::createForNewCC($this->purchaseProcess);
    }
}
