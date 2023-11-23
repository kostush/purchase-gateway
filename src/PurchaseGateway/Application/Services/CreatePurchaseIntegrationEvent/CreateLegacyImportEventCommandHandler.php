<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\Base\Application\Services\CommandHandler;
use ProBillerNG\Base\Domain\InvalidCommandException;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class CreateLegacyImportEventCommandHandler implements CommandHandler
{
    use CreateLegacyImportEventBase;

    public const WORKER_NAME = 'legacyCommunication';

    /**
     * CreateLegacyImportEventCommandHandler constructor.
     * @param TransactionService                $transactionService     Transaction service
     * @param BundleRepository                  $bundleRepository       Bundle repository
     * @param PaymentTemplateTranslatingService $paymentTemplateService PaymentTemplateTranslatingService
     * @param ServiceBusFactory                 $serviceBusFactory      ServiceBusFactory
     * @param ConfigService                     $configServiceClient    Config Service
     * @throws \Exception
     */
    public function __construct(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        PaymentTemplateTranslatingService $paymentTemplateService,
        ServiceBusFactory $serviceBusFactory,
        ConfigService $configServiceClient
    ) {
        $this->init(
            $transactionService,
            $bundleRepository,
            $paymentTemplateService,
            $serviceBusFactory,
            $configServiceClient
        );
    }

    /**
     * @param Command $command Command to execute
     *
     * @return void
     *
     * @throws LoggerException
     * @throws CannotCreateIntegrationEventException
     * @throws InvalidCommandException
     */
    public function execute(Command $command): void
    {
        if (!$command instanceof ConsumeEventCommand) {
            throw new InvalidCommandException(ConsumeEventCommand::class, $command);
        }

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson($command->eventBody());

        //If the legacy api import is on than do nothing with legacy worker import
        if (config('app.feature.legacy_api_import') && $purchaseProcessedEvent->isImportedByApi() === true) {
            Log::info("Legacy api import is active so we don't do anything with legacy import enrich worker");

            return;
        }

        $this->handlePurchase($purchaseProcessedEvent);
    }
}
