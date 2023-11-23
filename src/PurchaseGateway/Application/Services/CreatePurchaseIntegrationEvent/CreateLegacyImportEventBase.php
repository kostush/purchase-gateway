<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use Hoa\Visitor\Test\Unit\Element;
use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseIntegrationEventBuilder;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;
use ProBillerNG\ServiceBus\Event;

trait CreateLegacyImportEventBase
{
    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var SessionId
     */
    protected $requestSessionId;

    /**
     * @var BundleRepository
     */
    protected $bundleRepository;

    /**
     * @var PaymentTemplateTranslatingService
     */
    protected $paymentTemplateService;

    /**
     * @var ServiceBusFactory
     */
    protected $serviceBusFactory;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * CreateLegacyImportEventCommandHandler constructor.
     * @param TransactionService                $transactionService     Transaction service
     * @param BundleRepository                  $bundleRepository       Bundle repository
     * @param PaymentTemplateTranslatingService $paymentTemplateService PaymentTemplateTranslatingService
     * @param ServiceBusFactory                 $serviceBusFactory      ServiceBusFactory
     * @param ConfigService                     $configServiceClient    Config Service
     * @return void
     *
     * @throws \Exception
     */
    public function init(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        PaymentTemplateTranslatingService $paymentTemplateService,
        ServiceBusFactory $serviceBusFactory,
        ConfigService $configServiceClient
    ): void {
        $this->transactionService     = $transactionService;
        $this->bundleRepository       = $bundleRepository;
        $this->paymentTemplateService = $paymentTemplateService;
        $this->serviceBusFactory      = $serviceBusFactory;
        $this->configServiceClient    = $configServiceClient;
        $this->requestSessionId       = SessionId::createFromString(Log::getSessionId());
    }

    /**
     * @return SessionId
     * @codeCoverageIgnore
     */
    public function requestSession(): SessionId
    {
        return $this->requestSessionId;
    }

    /**
     * @return TransactionService
     * @codeCoverageIgnore
     */
    public function transactionService(): TransactionService
    {
        return $this->transactionService;
    }

    /**
     * @return BundleRepository
     */
    public function bundleRepository(): BundleRepository
    {
        return $this->bundleRepository;
    }

    /**
     * @return SiteRepository
     */
    public function siteRepository(): SiteRepository
    {
        return $this->siteRepository;
    }

    /**
     * @return PaymentTemplateTranslatingService
     */
    public function paymentTemplateService(): PaymentTemplateTranslatingService
    {
        return $this->paymentTemplateService;
    }

    /**
     * @return ServiceBusFactory
     */
    public function serviceBusFactory(): ServiceBusFactory
    {
        return $this->serviceBusFactory;
    }

    /**
     * @param PurchaseProcessed $purchaseProcessedEvent PurchaseProcessed
     *
     * @return void
     *
     * @throws LoggerException
     * @throws CannotCreateIntegrationEventException
     * @throws \Exception
     */
    protected function handlePurchase(
        PurchaseProcessed $purchaseProcessedEvent
    ): void {
        $paymentTemplateData = null;
        if (!empty($purchaseProcessedEvent->payment()['paymentTemplateId'])) {
            $paymentTemplateData = $this->retrievePaymentTemplateData(
                $purchaseProcessedEvent->payment()['paymentTemplateId']
            );
        }

        $this->handleIntegrationEvent($purchaseProcessedEvent, $paymentTemplateData);
    }

    /**
     * @param PurchaseProcessed    $purchaseProcessedEvent The purchase event
     * @param PaymentTemplate|null $paymentTemplateData    PaymentTemplate
     *
     * @return void
     * @throws LoggerException
     * @throws CannotCreateIntegrationEventException
     */
    protected function handleIntegrationEvent(
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData = null
    ): void {
        // If transaction was not created, there is nothing to import, to keep legacy consistent.
        if (empty($purchaseProcessedEvent->transactionCollection())) {
            Log::info('Skipping import, transaction was not created');
            return;
        }

        // If transaction was aborted, there is nothing to import, to keep legacy consistent.
        if ($purchaseProcessedEvent->lastTransaction()['state'] === Transaction::STATUS_ABORTED) {
            Log::info('Skipping import, transaction was aborted');
            return;
        }

        Log::info('Processing event', ['purchaseId' => $purchaseProcessedEvent->purchaseId()]);

        $integrationEvent = $this->createPurchaseIntegrationEvent(
            $purchaseProcessedEvent,
            $paymentTemplateData
        );

        $this->publishIntegrationEvent($integrationEvent);

        Log::info('Event processed successfully', ['purchaseId' => $purchaseProcessedEvent->purchaseId()]);
    }

    /**
     * @param PurchaseEvent $integrationEvent Integration event
     * @return void
     */
    protected function publishIntegrationEvent(PurchaseEvent $integrationEvent): void
    {
        $serviceBus = $this->serviceBusFactory()->make();
        $serviceBus->publish(Event::create($integrationEvent, 'memberId'));
    }

    /**
     * @param string $transactionId The transaction id
     * @return RetrieveTransactionResult
     * @throws \Exception
     */
    protected function retrieveTransactionData($transactionId): RetrieveTransactionResult
    {
        return $this->transactionService()
            ->getTransactionDataBy(
                TransactionId::createFromString($transactionId),
                $this->requestSession()
            );
    }

    /**
     * @param string $templateId Template Id
     * @return PaymentTemplate PaymentTemplate
     */
    protected function retrievePaymentTemplateData(string $templateId)
    {
        return $this->paymentTemplateService()->retrievePaymentTemplate(
            $templateId,
            (string) $this->requestSession()
        );
    }

    /**
     * @param PurchaseProcessed    $purchaseProcessedEvent The purchase event
     * @param PaymentTemplate|null $paymentTemplateData    PaymentTemplate
     *
     * @return PurchaseEvent
     *
     * @throws LoggerException
     * @throws CannotCreateIntegrationEventException
     * @throws \Exception
     */
    protected function createPurchaseIntegrationEvent(
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData = null
    ): PurchaseEvent {
        $mainTransactionData = $this->retrieveTransactionData($purchaseProcessedEvent->lastTransactionId());

        $site = $this->retrieveSite($purchaseProcessedEvent->siteId());

        $purchaseIntegrationEvent = PurchaseIntegrationEventBuilder::build(
            $mainTransactionData,
            $purchaseProcessedEvent,
            $paymentTemplateData
        );

        $bundle = $this->bundleRepository()->findBundleByBundleAddon(
            BundleId::createFromString($purchaseProcessedEvent->bundleId()),
            AddonId::createFromString($purchaseProcessedEvent->addOnId())
        );

        $mainPurchaseData =  array_merge(
            $purchaseProcessedEvent->toArray(),
            ['state' => $purchaseProcessedEvent->lastTransaction()['state']]
        );

        // For secRev with payment template and NFS we don't import so we set NFS flag false for import
        if($paymentTemplateData !== null) {
            $mainPurchaseData['isNsf'] = false;
        }

        // Add main item
        $mainItem = PurchaseIntegrationEventBuilder::buildItem(
            $mainPurchaseData,
            $mainTransactionData,
            $bundle,
            null,
            $site
        );

        $purchaseIntegrationEvent->addItem($mainItem);

        foreach ($purchaseProcessedEvent->crossSalePurchaseData() as $crossSalePurchaseData) {
            // Do not add cross sale if transaction not created
            if (empty($crossSalePurchaseData['transactionCollection'])) {
                Log::info('No transactions for the cross-sell. Probably because the main purchase was not successful.');
                continue;
            }

            // Do not add cross sale if transaction aborted
            $crossSaleStatus = end($crossSalePurchaseData['transactionCollection'])['state'];
            if ($crossSaleStatus === Transaction::STATUS_ABORTED) {
                Log::info('Skipping import, transaction was aborted');
                continue;
            }

            $crossSaleTransactionId    = $purchaseProcessedEvent->lastCrossSaleTransactionId($crossSalePurchaseData);
            $crossSellsTransactionData = $this->retrieveTransactionData($crossSaleTransactionId);

            // For cross-sale when it's secRev with payment template and NFS
            if($paymentTemplateData !== null) {
                $crossSalePurchaseData['isNsf'] = false;
            }

            $bundle = $this->bundleRepository()->findBundleByBundleAddon(
                BundleId::createFromString($crossSalePurchaseData['bundleId']),
                AddonId::createFromString($crossSalePurchaseData['addonId'])
            );

            $crossSalePurchaseData['state'] = $crossSaleStatus;

            // Add cross sale item
            $crossSaleItem = PurchaseIntegrationEventBuilder::buildItem(
                $crossSalePurchaseData,
                $crossSellsTransactionData,
                $bundle,
                $purchaseProcessedEvent->subscriptionId(),
                $site
            );

            // Add cross sale item
            $purchaseIntegrationEvent->addItem($crossSaleItem);
        }

        return $purchaseIntegrationEvent;
    }

    /**
     * @param string $siteId The site id
     * @return Site
     * @throws \Exception
     */
    protected function retrieveSite(string $siteId): Site
    {
        return $this->configServiceClient->getSite($siteId);
    }
}
