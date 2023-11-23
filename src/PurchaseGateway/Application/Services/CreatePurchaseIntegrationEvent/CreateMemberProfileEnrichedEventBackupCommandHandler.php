<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Projection\Application\Service\BaseTrackingWorkerHandler;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasSuccessfulEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasUnsuccessfulEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseProcessedEnrichedEvent;
use ProBillerNG\PurchaseGateway\Application\Services\ExposeIntegrationEvents;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEventHandling;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\ServiceBus\Event as MessageEvent;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;
use ProBillerNG\ServiceBus\InvalidMessageException;

class CreateMemberProfileEnrichedEventBackupCommandHandler extends BaseTrackingWorkerHandler implements ExposeIntegrationEvents
{
    use IntegrationEventHandling;
    use CreateMemberProfileEnrichedEventBase;

    public const WORKER_NAME = 'member-profile-enriched-event';

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var BundleRepository
     */
    protected $bundleRepository;

    /**
     * @var ServiceBusFactory
     */
    protected $serviceBusFactory;

    /**
     * @var SessionId
     */
    protected $requestSessionId;

    /**
     * CreateMemberProfileEnrichedEventCommandHandler constructor.
     *
     * @param TransactionService $transactionService  Transaction service
     * @param BundleRepository   $bundleRepository    Bundle repository
     * @param Projectionist      $projectionist       Projectionist
     * @param ItemSourceBuilder  $itemSourceBuilder   Item source builder
     * @param ServiceBusFactory  $serviceBusFactory   ServiceBusFactory
     * @param ConfigService      $configServiceClient ConfigService
     *
     * @throws \Exception
     */
    public function __construct(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        Projectionist $projectionist,
        ItemSourceBuilder $itemSourceBuilder,
        ServiceBusFactory $serviceBusFactory,
        ConfigService $configServiceClient
    ) {
        parent::__construct($projectionist, $itemSourceBuilder);

        $this->init($transactionService, $bundleRepository, $serviceBusFactory, $configServiceClient);
    }

    /**
     * @param ItemToWorkOn $item Item to handle
     *
     * @return void
     *
     * @throws LoggerException
     * @throws \Exception
     */
    protected function operation(ItemToWorkOn $item): void
    {
        $purchaseProcessedEvent = PurchaseProcessed::createFromJson($item->body());

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

            $this->persistIntegrationEvents();

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
     * @throws InvalidMessageException
     */
    public function handleEnrichedEvent($purchaseProcessedEnrichedEvent): void
    {
        Log::info('createPurchaseEnrichedEvent: add integration event to collection.');

        $this->addIntegrationEvent($purchaseProcessedEnrichedEvent);

        $messageEvent = new MessageEvent($purchaseProcessedEnrichedEvent->toArray());

        Log::info('createPurchaseEnrichedEvent: before publication.');
        $serviceBus = $this->serviceBusFactory()->make();
        $serviceBus->publish($messageEvent);
        Log::info('Published message', ['type' => $messageEvent->type(), 'body' => $messageEvent->body()]);
        Log::info('createPurchaseEnrichedEvent: after publication.', $purchaseProcessedEnrichedEvent->toArray());
    }
}
