<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Base\Domain\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasSuccessfulEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasUnsuccessfulEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseProcessedEnrichedEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\TransientConfigServiceException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;

trait CreateMemberProfileEnrichedEventBase
{
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
     * @var ConfigService
     */
    private $configServiceClient;

    /**
     * CreateMemberProfileEnrichedEventCommandHandler constructor.
     * @param TransactionService $transactionService  Transaction service
     * @param BundleRepository   $bundleRepository    Bundle repository
     * @param ServiceBusFactory  $serviceBusFactory   ServiceBusFactory
     * @param ConfigService      $configServiceClient Config Service
     * @return void
     *
     * @throws \Exception
     */
    public function init(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        ServiceBusFactory $serviceBusFactory,
        ConfigService $configServiceClient
    ): void {
        $this->transactionService  = $transactionService;
        $this->bundleRepository    = $bundleRepository;
        $this->serviceBusFactory   = $serviceBusFactory;
        $this->configServiceClient = $configServiceClient;
        $this->requestSessionId    = SessionId::createFromString(Log::getSessionId());
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
     * @return ServiceBusFactory
     * @codeCoverageIgnore
     */
    public function serviceBusFactory(): ServiceBusFactory
    {
        return $this->serviceBusFactory;
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
     * @param string $siteId
     *
     * @return Site
     * @throws TransientConfigServiceException
     */
    protected function retrieveSite(string $siteId): Site
    {
        try {
            $site = $this->configServiceClient->getSite($siteId);

            if($site instanceof Site){
                return $site;
            }
            throw new \Exception('Site can not be null, ConfigServiceClient returned null for getSite request');
        }catch (\Throwable $e){
            throw new TransientConfigServiceException($e, 'Site retrieve failed');
        }
    }

    /**
     * @param PurchaseProcessed      $purchaseProcessed      purchase processed
     * @param TransactionInformation $transactionInformation transaction information
     *
     * @return void
     * @throws LoggerException
     * @throws \Exception
     */
    protected function createPurchaseEnrichedEvent(
        PurchaseProcessed $purchaseProcessed,
        TransactionInformation $transactionInformation
    ): void {
        Log::info('createPurchaseEnrichedEvent: Initial message.');

        $bundleIds   = [];
        $addonsIds   = [];
        $bundleIds[] = BundleId::createFromString($purchaseProcessed->bundleId());
        $addonsIds[] = BundleId::createFromString($purchaseProcessed->addOnId());

        if (!empty($purchaseProcessed->crossSalePurchaseData())) {
            foreach ($purchaseProcessed->crossSalePurchaseData() as $crossSaleData) {
                $bundleIds[] = BundleId::createFromString($crossSaleData['bundleId']);
                $addonsIds[] = BundleId::createFromString($crossSaleData['addonId']);
            }
        }

        $site = $this->retrieveSite($purchaseProcessed->siteId());

        $bundles = $this->bundleRepository()->findBundleByIds($bundleIds, $addonsIds);

        $purchaseProcessedEnrichedEvent = PurchaseProcessedEnrichedEvent::createFromTransactionAndPurchase(
            $purchaseProcessed,
            $transactionInformation,
            $bundles,
            $site
        );

        $this->handleEnrichedEvent($purchaseProcessedEnrichedEvent);
    }

    /**
     * @param PurchaseProcessed      $purchaseProcessed      Purchase processed.
     * @param TransactionInformation $transactionInformation Transaction information.
     * @return void
     * @throws LoggerException
     * @throws \Exception
     */
    protected function createBundleRebillEvent(
        PurchaseProcessed $purchaseProcessed,
        TransactionInformation $transactionInformation
    ):void {
        if ($transactionInformation->status() === Transaction::STATUS_ABORTED) {
            return;
        }

        if ($transactionInformation->status() === Transaction::STATUS_DECLINED) {
            $rebillEnrichedEvent = BundleRebillWasUnsuccessfulEvent::createFromPurchase(
                $purchaseProcessed,
                $transactionInformation
            );
        } else {
            $rebillEnrichedEvent = BundleRebillWasSuccessfulEvent::createFromPurchase(
                $purchaseProcessed,
                $transactionInformation
            );
        }

        Log::info('createBundleEvent of type' . $rebillEnrichedEvent::INTEGRATION_NAME);

        $this->handleEnrichedEvent($rebillEnrichedEvent);

        Log::info('createBundleEvent: Initial message.');
    }


    /**
     * @param PurchaseProcessedEnrichedEvent|BundleRebillWasSuccessfulEvent|BundleRebillWasUnsuccessfulEvent $purchaseProcessedEnrichedEvent Event
     * @return void
     */
    abstract public function handleEnrichedEvent($purchaseProcessedEnrichedEvent): void;
}
