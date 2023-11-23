<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\Base\Application\Services\CommandHandler;
use ProBillerNG\Base\Domain\InvalidCommandException;
use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasSuccessfulEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasUnsuccessfulEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseProcessedEnrichedEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\ServiceBus\Event as MessageEvent;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;

class CreateMemberProfileEnrichedEventCommandHandler implements CommandHandler
{
    use CreateMemberProfileEnrichedEventBase;

    public const WORKER_NAME = 'memberProfileCommunication';

    /**
     * CreateMemberProfileEnrichedEventCommandHandler constructor.
     * @param TransactionService $transactionService Transaction service
     * @param BundleRepository   $bundleRepository   Bundle repository
     * @param ServiceBusFactory  $serviceBusFactory  ServiceBusFactory
     * @param ConfigService      $configServiceClient    ConfigService
     * @throws \Exception
     */
    public function __construct(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        ServiceBusFactory $serviceBusFactory,
        ConfigService $configServiceClient
    ) {
        $this->init($transactionService, $bundleRepository, $serviceBusFactory, $configServiceClient);
    }

    /**
     * @param Command $command Command to execute
     *
     * @return void
     *
     * @throws LoggerException
     * @throws InvalidCommandException
     * @throws \Exception
     */
    public function execute(Command $command): void
    {
        if (!$command instanceof ConsumeEventCommand) {
            throw new InvalidCommandException(ConsumeEventCommand::class, $command);
        }
        $purchaseProcessedEvent = PurchaseProcessed::createFromJson($command->eventBody());

        if ($purchaseProcessedEvent->subscriptionId()) {
            // Create event for main purchase
            $transactionId       = $purchaseProcessedEvent->lastTransactionId();
            $mainTransactionData = $this->retrieveTransactionData($transactionId);

            Log::info('execute: before create purchase enriched event.');

            if ($mainTransactionData instanceof QyssoRetrieveTransactionResult
                && $mainTransactionData->type() == QyssoRetrieveTransactionResult::TYPE_REBILL
            ) {
                $this->createBundleRebillEvent($purchaseProcessedEvent, $mainTransactionData->transactionInformation());
            } else {
                $this->createPurchaseEnrichedEvent(
                    $purchaseProcessedEvent,
                    $mainTransactionData->transactionInformation()
                );
            }

            Log::info('execute: after create purchase enriched event.');
        } else {
            Log::info('Skipping sending enriched event (no subscription id provided)');
        }
    }

    /**
     * @param PurchaseProcessedEnrichedEvent|BundleRebillWasSuccessfulEvent|BundleRebillWasUnsuccessfulEvent $purchaseProcessedEnrichedEvent Event
     * @return void
     *
     * @throws LoggerException
     */
    public function handleEnrichedEvent($purchaseProcessedEnrichedEvent): void
    {
        Log::info('createPurchaseEnrichedEvent: add integration event to collection.');

        $messageEvent = MessageEvent::create($purchaseProcessedEnrichedEvent, 'memberId');

        Log::info('createPurchaseEnrichedEvent: before publication.');
        $serviceBus = $this->serviceBusFactory()->make();
        $serviceBus->publish($messageEvent);
        Log::info('Published message', ['type' => $messageEvent->type(), 'body' => $messageEvent->body()]);
        Log::info('createPurchaseEnrichedEvent: after publication.', $purchaseProcessedEnrichedEvent->toArray());
    }
}
