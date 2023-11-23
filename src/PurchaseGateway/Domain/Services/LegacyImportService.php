<?php

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use Exception;
use Illuminate\Http\Response;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseIntegrationEventBuilder;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class LegacyImportService
{
    const EVENT                        = 'event';

    const BILLING_GATEWAY_API_ENDPOINT = 'api/TransactionImport/NGEvent';

    const SUCCESS                      = 'success';

    const FAILED                       = 'failed';

    /**@var TransactionService */
    protected $transactionService;

    /** @var SessionId */
    protected $requestSessionId;

    /** @var BundleRepository */
    protected $bundleRepository;

    /** @var PaymentTemplateTranslatingService */
    protected $paymentTemplateService;

    /** @var ConfigService */
    private $configService;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * CreateLegacyImportEventCommandHandler constructor.
     *
     * @param TransactionService                $transactionService     Transaction service
     * @param BundleRepository                  $bundleRepository       Bundle repository
     * @param PaymentTemplateTranslatingService $paymentTemplateService PaymentTemplateTranslatingService
     * @param ConfigService                     $configService          ConfigService
     *
     * @throws Exception
     */
    public function __construct(
        TransactionService $transactionService,
        BundleRepository $bundleRepository,
        PaymentTemplateTranslatingService $paymentTemplateService,
        ConfigService $configService
    ) {
        $this->transactionService     = $transactionService;
        $this->bundleRepository       = $bundleRepository;
        $this->paymentTemplateService = $paymentTemplateService;
        $this->configService          = $configService;
        $this->requestSessionId       = SessionId::createFromString(Log::getSessionId());
        $this->serializer             = SerializerBuilder::create()->build();
    }

    /**
     * @param PurchaseProcessed $purchaseProcessedEvent
     *
     * @return PurchaseProcessed|void
     * @throws LoggerException
     */
    public function handlerLegacyImportByApiEndPoint(PurchaseProcessed $purchaseProcessedEvent)
    {
        try {
            $paymentTemplateData = null;
            if (!empty($purchaseProcessedEvent->payment()['paymentTemplateId'])) {
                $paymentTemplateData = $this->retrievePaymentTemplateData(
                    $purchaseProcessedEvent->payment()['paymentTemplateId']
                );
            }

            // If transaction was not created, there is nothing to import, to keep legacy consistent.
            if (empty($purchaseProcessedEvent->transactionCollection())) {
                Log::info(
                    'LegacyImportService skipping import, transaction was not created',
                    [$this->serializeToJSON($purchaseProcessedEvent->toArray())]
                );

                return;
            }

            // If transaction was aborted, there is nothing to import, to keep legacy consistent.
            if ($purchaseProcessedEvent->lastTransaction()['state'] === Transaction::STATUS_ABORTED) {
                Log::info(
                    'LegacyImportService skipping import, transaction was aborted',
                    [$this->serializeToJSON($purchaseProcessedEvent->toArray())]
                );

                return;
            }

            $integrationEvent = $this->createPurchaseIntegrationEvent(
                $purchaseProcessedEvent,
                $paymentTemplateData
            );

            // Here create the proper message to send on legacy
            $requestBody                 = $integrationEvent->toArray();
            $requestBody['message_type'] = self::EVENT;
            $request['body']             = $requestBody;
            $request['type']             = get_class($integrationEvent);

            Log::info(
                'LegacyImportService sending request to billing gateway import endpoint.',
                [
                    'requestType'    => 'CURL',
                    'endpoint'       => env('BILLING_GATEWAY_URL') . self::BILLING_GATEWAY_API_ENDPOINT,
                    'host'           => env('BILLING_GATEWAY_URL'),
                    'requestPayload' => [
                        'request' => [$this->serializer->serialize($request, 'json')]
                    ]
                ]
            );

            $response = $this->legacyCURLCall($request);
            $this->isImportSuccessfulAndUsernamePadded($request, $response, $purchaseProcessedEvent);
        } catch (Exception | CannotCreateIntegrationEventException $exception) {
            Log::error(
                'LegacyImportService import by api was not successful',
                [
                    'errorMessage' => $exception->getMessage(),
                    'apiImportRequestBody' => $requestBody ?? null
                ]
            );
        }

        return $purchaseProcessedEvent;
    }

    /**
     * @param                   $request
     * @param null|array        $responses
     * @param PurchaseProcessed $purchaseProcessedEvent
     *
     * @throws LoggerException
     */
    private function isImportSuccessfulAndUsernamePadded(
        array $request,
        ?array $responses,
        PurchaseProcessed $purchaseProcessedEvent
    ): void {
        $importedItem         = [];
        $shouldLogForFailedCrossSalesEvent = false;

        $memberInfo       = $purchaseProcessedEvent->memberInfo();
        $crossSaleData    = $purchaseProcessedEvent->crossSalePurchaseData();
        $originalUsername = $purchaseProcessedEvent->subscriptionUsername();

        /**
         * Determine if username was padded or not and if padded set usernamePadded true which will be used on email and
         * also set that new padded username to subscription username which is used by member profile
         * and username under member info as it's used on email.
         */
        if ($this->isCurlCallSuccessful($responses)) {
            // initially we consider event successfully imported if response with status code 0 was returned
            $purchaseProcessedEvent->setIsImportedByApi(true);
            foreach ($responses as $key => $response) {
                if (isset($response['subscription']) && $response['status'] === self::SUCCESS) {
                    $importedItem[$response['itemId']] = $response['status'];

                    $isParentSubscriptionIdPresent = empty($response['subscription']['_parentSubscriptionId']);
                    $isUsernamePadded              = !empty($response['subscription']['_username'])
                                                     && $response['subscription']['_username'] !== $originalUsername;
                    if ($isUsernamePadded && $isParentSubscriptionIdPresent) {
                        // set the changes to purchase processed event for main product only
                        $purchaseProcessedEvent->usernamePadded();
                        $purchaseProcessedEvent->setSubscriptionUsername($response['subscription']['_username']);
                        $memberInfo['username'] = !empty($memberInfo['username']) ? $response['subscription']['_username'] : null;
                    }
                } elseif (isset($response['itemId']) && $response['status'] === self::FAILED) {
                    $shouldLogForFailedCrossSalesEvent              = true;
                    $importedItem[$response['itemId']] = $response['status'];
                }

                // set username, password and isUsernamePadded to each cross-sale
                foreach ($crossSaleData as $cIndex => $crossSale) {
                    if (isset($response['itemId']) && $crossSale['itemId'] == $response['itemId']) {
                        $isCrossSaleUsernamePadded = !empty($response['subscription']['_username'])
                                                     && $response['subscription']['_username'] !== $originalUsername;

                        $crossSaleData[$cIndex]['isUsernamePadded'] = $isCrossSaleUsernamePadded;

                        $crossSaleData[$cIndex]['subscriptionUsername'] = $response['subscription']['_username'] ?? $originalUsername;
                        $crossSaleData[$cIndex]['subscriptionPassword'] = $memberInfo['password'];
                    }
                }
            }

            //Set back the member and cross-sale info to the purchaseProcessed domain event
            $purchaseProcessedEvent->setMemberInfo($memberInfo);
            $purchaseProcessedEvent->setCrossSalePurchaseData($crossSaleData);

            if ($shouldLogForFailedCrossSalesEvent) {
                Log::error(
                    'LegacyImportFailure only cross-sales item failed to import on legacy by api it will be published on rabbitmq',
                    [
                        'requestType'    => 'CURL',
                        'endpoint'       => env('BILLING_GATEWAY_URL') . self::BILLING_GATEWAY_API_ENDPOINT,
                        'host'           => env('BILLING_GATEWAY_URL'),
                        'requestPayload' => [
                            'request' => [$this->serializer->serialize($request, 'json')]
                        ]
                    ]
                );

                // we consider event was not successfully imported if response has cross sale item with Failed status
                $purchaseProcessedEvent->setIsImportedByApi(false);
            }
        } else {
            Log::info(
                'LegacyImportFailure full purchase failed to import on legacy by api it will be published on rabbitmq',
                [
                    'requestType'    => 'CURL',
                    'endpoint'       => env('BILLING_GATEWAY_URL') . self::BILLING_GATEWAY_API_ENDPOINT,
                    'host'           => env('BILLING_GATEWAY_URL'),
                    'requestPayload' => [
                        'request' => [$this->serializer->serialize($request, 'json')]
                    ]
                ]
            );
        }
    }

    /**
     * @param $response
     * @return bool
     */
    protected function isCurlCallSuccessful($response): bool
    {
        if (empty($response) || !is_array($response)) {
            return false;
        }

        if (isset($response['code']) && $response['code'] == 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array $data
     *
     * @return mixed
     * @throws LoggerException
     */
    public function  legacyCURLCall(array $data): array
    {
        // API URL
        $url = env('BILLING_GATEWAY_URL', 'http://billing.local/') . self::BILLING_GATEWAY_API_ENDPOINT;

        // Create a new cURL resource
        $ch = curl_init($url);

        // Setup request to send json via POST
        $payload = $this->serializer->serialize($data, 'json');

        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);

        $useNoSignal = false;

        $httpConnectionTimeout = (int) env('BILLING_GATEWAY_HTTP_CONNECTION_TIMEOUT', '10');
        if ($httpConnectionTimeout < 1) {
            // Connection Timeout in milliseconds
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $httpConnectionTimeout * 1000);
            $useNoSignal = true;
        } else {
            // Connection Timeout in seconds
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $httpConnectionTimeout);
        }

        $httpExecTimeout = (int) env('BILLING_GATEWAY_HTTP_EXEC_TIMEOUT', '10');
        if ($httpExecTimeout < 1) {
            // Timeout in milliseconds
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $httpExecTimeout * 1000);
            $useNoSignal = true;
        } else {
            // Timeout in seconds
            curl_setopt($ch, CURLOPT_TIMEOUT, $httpExecTimeout);
        }

        /**
         * If connection timeout or execution timeout is less than a second, we need to send this flag
         * @link http://php.net/manual/en/function.curl-setopt.php#104597
         */
        if ($useNoSignal) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        }

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close cURL resource
        curl_close($ch);

        if ($httpCode != Response::HTTP_OK) {
            Log::info(
                'RESPONSE from billing gateway import.',
                [
                    'requestType'     => 'CURL',
                    'endpoint'        => self::BILLING_GATEWAY_API_ENDPOINT,
                    'host'            => env('BILLING_GATEWAY_URL') . env('BILLING_GATEWAY_URL'),
                    'responsePayload' => [
                        'response' => $result
                    ]
                ]
            );

            return [];
        }

        $response = json_decode($result, true);
        Log::info(
            'RESPONSE from billing gateway import endpoint.',
            [
                'requestType'     => 'CURL',
                'endpoint'        => self::BILLING_GATEWAY_API_ENDPOINT,
                'host'            => env('BILLING_GATEWAY_URL') . env('BILLING_GATEWAY_URL'),
                'responsePayload' => [
                    'response' => $response
                ]
            ]
        );

        if (empty($response)) {
            Log::error("Decoded legacy curl call is empty", ['payload' => $payload]);
            return [];
        }

        return $response;
    }

    /**
     * @param string $transactionId The transaction id
     *
     * @return RetrieveTransactionResult
     * @throws Exception
     */
    protected function retrieveTransactionData(string $transactionId): RetrieveTransactionResult
    {
        return $this->transactionService
            ->getTransactionDataBy(
                TransactionId::createFromString($transactionId),
                $this->requestSessionId
            );
    }

    /**
     * @param string $templateId Template Id
     *
     * @return PaymentTemplate PaymentTemplate
     */
    protected function retrievePaymentTemplateData(string $templateId)
    {
        return $this->paymentTemplateService->retrievePaymentTemplate(
            $templateId,
            (string) $this->requestSessionId
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
     * @throws Exception
     */
    protected function createPurchaseIntegrationEvent(
        PurchaseProcessed $purchaseProcessedEvent,
        ?PaymentTemplate $paymentTemplateData = null
    ): PurchaseEvent {
        $mainTransactionData = $this->retrieveTransactionData($purchaseProcessedEvent->lastTransactionId());

        $site = $this->configService->getSite($purchaseProcessedEvent->siteId());

        $purchaseIntegrationEvent = PurchaseIntegrationEventBuilder::build(
            $mainTransactionData,
            $purchaseProcessedEvent,
            $paymentTemplateData
        );

        $bundle = $this->bundleRepository->findBundleByBundleAddon(
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
                Log::info(
                    'No transactions for the cross-sell. Probably because the main purchase was not successful.',
                    [$this->serializeToJSON($purchaseProcessedEvent->toArray())]
                );
                continue;
            }

            // Do not add cross sale if transaction aborted
            $crossSaleStatus = end($crossSalePurchaseData['transactionCollection'])['state'];
            if ($crossSaleStatus === Transaction::STATUS_ABORTED) {
                Log::info(
                    'Skipping import, transaction was aborted for cross-sale.',
                    [$this->serializeToJSON($purchaseProcessedEvent->toArray())]
                );
                continue;
            }

            $crossSaleTransactionId    = $purchaseProcessedEvent->lastCrossSaleTransactionId($crossSalePurchaseData);
            $crossSellsTransactionData = $this->retrieveTransactionData($crossSaleTransactionId);

            // For cross-sale when it's secRev with payment template and NFS
            if($paymentTemplateData !== null) {
                $crossSalePurchaseData['isNsf'] = false;
            }

            $bundle = $this->bundleRepository->findBundleByBundleAddon(
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
     *
     * @return Site
     * @throws Exception
     */
    protected function retrieveSite(string $siteId): Site
    {
        return $this->configService->getSite($siteId);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function serializeToJSON(array $data): string
    {
        return $this->serializer->serialize($data, 'json');
    }
}
