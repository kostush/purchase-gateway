<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Projection\Application\Service\BaseTrackingWorkerHandler;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;

class CreateLegacyImportEventBackupCommandHandler extends BaseTrackingWorkerHandler
{
    use CreateLegacyImportEventBase;

    public const WORKER_NAME = 'legacy-import';

    /**
     * CreateLegacyImportEventCommandHandler constructor.
     * @param TransactionService                $transactionService     Transaction service
     * @param BundleRepository                  $bundleRepository       Bundle repository
     * @param Projectionist                     $projectionist          Projectionist
     * @param ItemSourceBuilder                 $itemSourceBuilder      Item source builder
     * @param PaymentTemplateTranslatingService $paymentTemplateService PaymentTemplateTranslatingService
     * @param ServiceBusFactory                 $serviceBusFactory      ServiceBusFactory
     * @param ConfigService                     $configServiceClient    Config Service
     * @throws \Exception
     */
    public function __construct(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        Projectionist $projectionist,
        ItemSourceBuilder $itemSourceBuilder,
        PaymentTemplateTranslatingService $paymentTemplateService,
        ServiceBusFactory $serviceBusFactory,
        ConfigService $configServiceClient
    ) {
        parent::__construct($projectionist, $itemSourceBuilder);

        $this->init(
            $transactionService,
            $bundleRepository,
            $paymentTemplateService,
            $serviceBusFactory,
            $configServiceClient
        );
    }

    /**
     * @param ItemToWorkOn $item Item to handle
     *
     * @return void
     *
     * @throws LoggerException
     * @throws CannotCreateIntegrationEventException
     */
    protected function operation(ItemToWorkOn $item): void
    {
        $purchaseProcessedEvent = PurchaseProcessed::createFromJson($item->body());

        $this->handlePurchase($purchaseProcessedEvent);
    }
}
