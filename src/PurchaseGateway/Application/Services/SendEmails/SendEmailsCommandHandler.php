<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\SendEmails;

use Exception;
use Probiller\Common\EmailSettings;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Application\Service\BaseTrackingWorkerHandler;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\BI\EmailForPurchaseSent;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxType;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailService;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\EmailTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class SendEmailsCommandHandler extends BaseTrackingWorkerHandler
{
    public const WORKER_NAME                = 'send-email';
    public const US_COUNTRY_CODE            = 'US';

    /**
     * @var string
     */
    public const MEMBER_TYPE_NEW = 'new';

    /**
     * @var string
     */
    public const MEMBER_TYPE_EXISTING = 'existing';

    /**
     * @var string
     */
    public const EMAIL_SUBJECT = 'Transaction confirmation';

    /**
     * @var string
     */
    private const DEFAULT_FROM = 'welcome@probiller.com';

    /**
     * @var string
     */
    private const DEFAULT_SENDER_ID = '1';

    /**
     * @var EmailService
     */
    private $emailService;

    /**
     * @var EmailTemplateService
     */
    private $emailTemplateService;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * @var BILoggerService
     */
    private $biLoggerService;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var MemberProfileGatewayService
     */
    protected $memberProfileGatewayService;

    /**
     * @var SessionId
     */
    protected $requestSessionId;

    /**
     * @var EmailSettingsService
     */
    private $emailSettingsService;

    /**
     * @var PaymentTemplateTranslatingService
     */
    private $paymentTemplateService;
    /**
     * @var EventIngestionService
     */
    private $eventIngestionService;

    /**
     * @var CCTransactionInformation|TransactionInformation|CheckTransactionInformation|null
     */
    private $transactionInformation;

    /**
     * SendEmailsCommandHandler constructor.
     *
     * @param Projectionist                     $projectionist               Projectionist
     * @param ItemSourceBuilder                 $itemSourceBuilder           Item source builder
     * @param TransactionService                $transactionService          Transaction Processing Handler
     * @param EmailService                      $emailService                Email service
     * @param ConfigService                     $configServiceClient         Config Service
     * @param EmailTemplateService              $emailTemplateService        Email Template Service
     * @param BILoggerService                   $bILoggerService             Bi Logger Service
     * @param EmailSettingsService              $emailSettingsService        Email Settings Service.
     * @param MemberProfileGatewayService       $memberProfileGatewayService Member Profile Gateway Service.
     * @param PaymentTemplateTranslatingService $paymentTemplateService      Payment Template Service.
     * @param EventIngestionService             $eventIngestionService       Event Ingestion Service
     *
     * @throws Exception
     */
    public function __construct(
        Projectionist $projectionist,
        ItemSourceBuilder $itemSourceBuilder,
        TransactionService $transactionService,
        EmailService $emailService,
        ConfigService $configServiceClient,
        EmailTemplateService $emailTemplateService,
        BILoggerService $bILoggerService,
        EmailSettingsService $emailSettingsService,
        MemberProfileGatewayService $memberProfileGatewayService,
        PaymentTemplateTranslatingService $paymentTemplateService,
        EventIngestionService $eventIngestionService
    ) {
        parent::__construct($projectionist, $itemSourceBuilder);

        $this->transactionService          = $transactionService;
        $this->emailService                = $emailService;
        $this->emailTemplateService        = $emailTemplateService;
        $this->configServiceClient         = $configServiceClient;
        $this->biLoggerService             = $bILoggerService;
        $this->emailSettingsService        = $emailSettingsService;
        $this->requestSessionId            = SessionId::createFromString(Log::getSessionId());
        $this->memberProfileGatewayService = $memberProfileGatewayService;
        $this->paymentTemplateService      = $paymentTemplateService;
        $this->eventIngestionService       = $eventIngestionService;
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
     * @return MemberProfileGatewayService
     * @codeCoverageIgnore
     */
    public function memberProfileGatewayService(): MemberProfileGatewayService
    {
        return $this->memberProfileGatewayService;
    }

    /**
     * @return EmailService
     * @codeCoverageIgnore
     */
    public function emailService(): EmailService
    {
        return $this->emailService;
    }

    /**
     * @return SiteRepositoryReadOnly
     * @codeCoverageIgnore
     */
    public function siteRepository(): SiteRepositoryReadOnly
    {
        return $this->siteRepository;
    }

    /**
     * @return EmailTemplateService
     * @codeCoverageIgnore
     */
    public function emailTemplateService(): EmailTemplateService
    {
        return $this->emailTemplateService;
    }

    /**
     * @return BILoggerService
     * @codeCoverageIgnore
     */
    public function biLoggerService(): BILoggerService
    {
        return $this->biLoggerService;
    }

    /**
     * @return PaymentTemplateTranslatingService
     */
    public function paymentTemplateService(): PaymentTemplateTranslatingService
    {
        return $this->paymentTemplateService;
    }

    /**
     * @return EmailSettingsService
     */
    public function emailSettingsService(): EmailSettingsService
    {
        return $this->emailSettingsService;
    }

    /**
     * @param ItemToWorkOn $item Item to handle
     *
     * @return void
     *
     * @throws LoggerException
     * @throws InvalidUserInfoEmail
     * @throws Exception
     */
    protected function operation(ItemToWorkOn $item): void
    {
        Log::info('SendEmailHandler Starting the process of email sending functionality.');

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson($item->body());

        if ($purchaseProcessedEvent->lastTransaction()['state'] != Transaction::STATUS_APPROVED) {
            Log::info(
                'SendEmailHandler Email is not being send because transaction is not approved.',
                [
                    'Details' => [
                        'transaction_status' => $purchaseProcessedEvent->lastTransaction()['state'],
                    ],
                ]
            );

            return;
        }

        $this->handleMainPurchase(
            $purchaseProcessedEvent
        );

        if (!empty($purchaseProcessedEvent->crossSalePurchaseData())) {
            $this->handleCrossSalePurchases(
                $purchaseProcessedEvent
            );
        }
    }

    /**
     * @param PurchaseProcessed $purchaseProcessedEvent The purchase process event
     *
     * @return void
     *
     * @throws LoggerException
     * @throws InvalidUserInfoEmail
     * @throws Exception
     */
    protected function handleMainPurchase(
        PurchaseProcessed $purchaseProcessedEvent
    ): void {
        Log::info(
            'SendEmailHandler email for main purchase',
            [
                'purchaseId' => $purchaseProcessedEvent->purchaseId(),
                'itemId'     => $purchaseProcessedEvent->itemId(),
                'sessionId'  => $purchaseProcessedEvent->sessionId()
            ]
        );

        $this->sendEmail(
            $purchaseProcessedEvent,
            $purchaseProcessedEvent->lastTransactionId(),
            $purchaseProcessedEvent->siteId(),
            TaxType::createFromTaxInformation($purchaseProcessedEvent->amounts()),
            null
        );
    }

    /**
     * @param PurchaseProcessed $purchaseProcessedEvent The purchase process event
     *
     * @return void
     *
     * @throws LoggerException
     * @throws InvalidUserInfoEmail
     * @throws Exception
     */
    protected function handleCrossSalePurchases(
        PurchaseProcessed $purchaseProcessedEvent
    ): void {
        foreach ($purchaseProcessedEvent->crossSalePurchaseData() as $crossSalePurchaseData) {
            $transaction = end($crossSalePurchaseData['transactionCollection']);
            if ($transaction['state'] != Transaction::STATUS_APPROVED) {
                continue;
            }
            Log::info(
                'SendEmailHandler email for cross sale purchase',
                [
                    'purchaseId' => $purchaseProcessedEvent->purchaseId(),
                    'itemId'     => $crossSalePurchaseData['itemId'],
                    'sessionId'  => $purchaseProcessedEvent->sessionId()
                ]
            );

            $this->sendEmail(
                $purchaseProcessedEvent,
                $transaction['transactionId'],
                $crossSalePurchaseData['siteId'],
                TaxType::createFromTaxInformation($crossSalePurchaseData['tax'] ?? null),
                $crossSalePurchaseData
            );
        }
    }

    /**
     * @param PurchaseProcessed $purchaseProcessedEvent The purchase process event
     * @param string            $transactionId          TransactionId
     * @param string            $siteId                 Site id
     * @param string            $taxType                Tax Type
     *
     * @param array|null        $crossSalePurchaseData
     *
     * @return void
     *
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoUsername
     * @throws LoggerException
     * @throws UnknownBillerNameException
     * @throws Exception
     */
    protected function sendEmail(
        PurchaseProcessed $purchaseProcessedEvent,
        string $transactionId,
        string $siteId,
        string $taxType,
        ?array $crossSalePurchaseData = null
    ): void {
        $site     = $this->retrieveSite($siteId);
        $services = $site->services();

        if (!isset($services[ServicesList::EMAIL_SERVICE])) {
            Log::info(
                'SendEmailHandler Email service is not active for this site',
                [
                    'Details' => [
                        'siteId' => $siteId,
                    ],
                ]
            );

            return;
        }

        $memberType = self::MEMBER_TYPE_NEW;

        // From the business perspective we should consider crossSale as an existing member purchase in terms of sending emails
        if ($purchaseProcessedEvent->isExistingMember() || $crossSalePurchaseData !== null) {
            $memberType = self::MEMBER_TYPE_EXISTING;
        }

        $transactionData = $this->retrieveTransactionData($transactionId);
        Log::info(
            'SendEmailHandler - transaction details',
            [
                $transactionData->toArray()
            ]
        );

        $billerName = $transactionData->billerName();
        $biller     = BillerFactoryService::create($billerName);

        if ($biller->isThirdParty()) {
            Log::info(
                'SendEmailHandler For biller ' . $billerName . ' send email is not required',
                ['sessionId' => $purchaseProcessedEvent->sessionId()]
            );

            return;
        }

        try {

            Log::info(
                'SendEmailHandler Retrieve Email Settings request to config service',
                [
                    'siteId'        => $siteId,
                    'taxType'       => $taxType,
                    'billerName'    => $billerName,
                    'memberType'    => $memberType,
                    'sessionId'     => $purchaseProcessedEvent->sessionId(),
                    'transactionId' => $transactionId
                ]
            );

            $emailSettings = $this->emailSettingsService()->retrieveEmailSettings(
                $siteId,
                $taxType,
                $billerName,
                $memberType,
                $purchaseProcessedEvent->getBusinessTransactionOperationType($crossSalePurchaseData),
                $purchaseProcessedEvent->sessionId(),
                $transactionId,
                $purchaseProcessedEvent->subscriptionPurchaseIncludesNonRecurring($crossSalePurchaseData)
            );
        } catch (\Exception $e) {
            Log::warning(
                'SendEmailHandler Unable to retrieve emailSettings from config service',
                [
                    'siteId'        => $siteId,
                    'taxType'       => $taxType,
                    'billerName'    => $billerName,
                    'memberType'    => $memberType,
                    'sessionId'     => $purchaseProcessedEvent->sessionId(),
                    'transactionId' => $transactionId
                ]
            );

            throw $e;
        }


        if (! $emailSettings instanceof  EmailSettings) {
            Log::info('SendEmailHandler - Email service not enabled for this site ',
                [
                    'siteId'    => $siteId,
                    'taxType'   => $taxType,
                    'sessionId' => $purchaseProcessedEvent->sessionId(),
                ]
            );
            return;
        }

        Log::info(
            'SendEmailHandler - Email settings is enabled for this site and it will be sent',
            [
                'sessionId'       => $purchaseProcessedEvent->sessionId(),
                'emailTemplateId' => $emailSettings->getEmailTemplateId()
            ]
        );

        $siteServiceOptions = $services[ServicesList::EMAIL_SERVICE]->options();
        $memberInfo         = $this->buildMemberInfo($site, $purchaseProcessedEvent, $crossSalePurchaseData);
        $paymentInformation = $this->retrievePaymentTemplateData($purchaseProcessedEvent, $transactionData);

        if (empty($crossSalePurchaseData)) {
            $this->transactionInformation = $transactionData->transactionInformation();
        }

        if (!empty($this->transactionInformation) && $this->transactionInformation instanceof CCTransactionInformation) {
            Log::info(
                'SendEmailHandler - CC Transaction Information',
                [
                    'first6'    => $this->transactionInformation->first6() ?? null,
                    'last4'     => $this->transactionInformation->last4() ?? null,
                    'sessionId' => $purchaseProcessedEvent->sessionId()
                ]
            );
        } else {
            Log::info('SendEmailHandler - Transaction Information is empty.');
        }

        $templateData = $this->emailTemplateService()->getTemplate(
            $site,
            $purchaseProcessedEvent,
            $transactionData,
            $memberInfo,
            $paymentInformation,
            $crossSalePurchaseData,
            $this->transactionInformation
        );

        $templateDataArray = $templateData->templateData();

        // This is for main product
        if (empty($crossSalePurchaseData) && $purchaseProcessedEvent->isUsernamePadded()) {
            $templateDataArray['LOGIN_NAME_PADDED'] = true;
        }

        //for each cross-sale
        $isCrossSaleUserNamePadded = $crossSalePurchaseData['isUsernamePadded'] ?? false;
        if (!empty($crossSalePurchaseData) && $isCrossSaleUserNamePadded) {
            $templateDataArray['LOGIN_NAME_PADDED'] = true;
        }

        $isMemberFromUS = false;
        if (isset($purchaseProcessedEvent->memberInfo()['country'])
            && isset($purchaseProcessedEvent->memberInfo()['countryDetectedByIp'])
        ) {
            Log::info(
                'SendEmailHandler Determining if country code is ' . self::US_COUNTRY_CODE,
                [
                    'countryCodeFromInit'    => $purchaseProcessedEvent->memberInfo()['country'],
                    'countryCodeFromProcess' => $purchaseProcessedEvent->memberInfo()['countryDetectedByIp'],
                    'sessionId'              => $purchaseProcessedEvent->sessionId(),
                ]
            );

            $isMemberFromUS = $purchaseProcessedEvent->memberInfo()['country'] == self::US_COUNTRY_CODE
                || $purchaseProcessedEvent->memberInfo()['countryDetectedByIp'] == self::US_COUNTRY_CODE;
        }

        $templateDataArray['IS_US'] = $isMemberFromUS;

        //show only for main product
        if (empty($crossSalePurchaseData)) {
            $templateDataArray["IS_TRIAL"] = $purchaseProcessedEvent->isTrial();
        }

        $emailServiceSenderId = $emailSettings->getSenderId() ?? self::DEFAULT_SENDER_ID;

        Log::info(
            'SendEmailHandler starting sending email to email-service',
            [
                'sessionId'            => $purchaseProcessedEvent->sessionId(),
                'email'                => (string) $memberInfo->email(),
                'templateArrayData'    => $templateDataArray,
                'senderName'           => $siteServiceOptions['senderName'] ?? null,
                'senderEmail'          => $siteServiceOptions['senderEmail'] ?? self::DEFAULT_FROM,
                'requestSession'       => (string) $this->requestSession(),
                'emailServiceSenderId' => $emailServiceSenderId,
            ]
        );

        $emailResponse = $this->emailService()->send(
            (string) $emailSettings->getEmailTemplateId() ?? $templateData->templateId(),
            $memberInfo->email(),
            $templateDataArray,
            Overrides::create(
                $this->getSubject($emailSettings),
                $this->getFriendlyName($emailSettings),
                $this->getFrom($emailSettings)
            ),
            $this->requestSession(),
            $emailServiceSenderId
        );

        // todo cast
        Log::info(
            'SendEmailHandler email response from email-service',
            [
                'emailResponseSessionId' => (string) $emailResponse->sessionId(),
                'sessionId'              => (string) $purchaseProcessedEvent->sessionId(),
                'traceId'                => (string) $emailResponse->traceId()
            ]
        );

        $emailForPurchaseSentEvent = EmailForPurchaseSent::createFromEventAndTraceId(
            $memberInfo,
            $purchaseProcessedEvent,
            $emailResponse->traceId()
        );

        Log::info(
            'SendEmailHandler trigger emailForPurchaseSent bi event',
            [
                'emailResponseSessionId' => (string) $emailResponse->sessionId(),
                'sessionId'              => (string) $purchaseProcessedEvent->sessionId(),
                'traceId'                => (string) $emailResponse->traceId()
            ]
        );

        $this->biLoggerService()->write($emailForPurchaseSentEvent);
        if (config('app.feature.event_ingestion_communication.send_general_bi_events')) {
            $this->eventIngestionService->queue($emailForPurchaseSentEvent);
        }
        // After each request we need to regenerate the session id
        $this->requestSessionId = SessionId::create();
    }

    /**
     * @param string $transactionId The transaction id
     *
     * @return RetrieveTransactionResult
     *
     * @throws Exception
     */
    protected function retrieveTransactionData(string $transactionId): RetrieveTransactionResult
    {
        return $this->transactionService()
            ->getTransactionDataBy(
                TransactionId::createFromString($transactionId),
                $this->requestSession()
            );
    }

    /**
     * @param PurchaseProcessed         $purchaseProcessedEvent The purchase processed
     * @param RetrieveTransactionResult $transactionData        The transaction data
     *
     * @return PaymentTemplate|null
     *
     * @throws Exception
     */
    protected function retrievePaymentTemplateData(
        PurchaseProcessed $purchaseProcessedEvent,
        RetrieveTransactionResult $transactionData
    ): ?PaymentTemplate {
        $isCheckTransaction   = $transactionData->isCheckTransaction();
        $purchaseHasFirst6    = $this->purchaseHasFirst6($transactionData, $purchaseProcessedEvent);
        $hasPaymentTemplateId = isset($purchaseProcessedEvent->payment()['paymentTemplateId']);
        // We need to skip to retrieval of the payment template when:
        //    - Payment template is not available
        //    - First6 is available
        //    - The transaction was made with check
        if ($isCheckTransaction || $purchaseHasFirst6 || !$hasPaymentTemplateId) {
            Log::info(
                'SendEmailHandler PaymentTemplateRetrieval Skip retrieval.',
                [
                    'Details' => [
                        'isCheckTransaction'   => $isCheckTransaction,
                        'purchaseHasFirst6'    => $purchaseHasFirst6,
                        'hasPaymentTemplateId' => $hasPaymentTemplateId,
                    ],
                ]
            );

            return null;
        }
        Log::info(
            'SendEmailHandler PaymentTemplateRetrieval Retrieve payment template.',
            [
                'Details' => [
                    'paymentTemplateId' => $purchaseProcessedEvent->payment()['paymentTemplateId'],
                ],
            ]
        );

        return $this->paymentTemplateService()->retrievePaymentTemplate(
            $purchaseProcessedEvent->payment()['paymentTemplateId'],
            (string) $this->requestSession()
        );
    }

    /**
     * @param RetrieveTransactionResult $transactionData        Transaction Data
     * @param PurchaseProcessed         $purchaseProcessedEvent Purchase Processed Event
     * @return bool
     */
    private function purchaseHasFirst6(
        RetrieveTransactionResult $transactionData,
        PurchaseProcessed $purchaseProcessedEvent
    ): bool {
        if ($transactionData->transactionInformation() instanceof CCTransactionInformation) {
            return ($transactionData->transactionInformation()->first6() || isset($purchaseProcessedEvent->payment()['first6'])
        );
    }

        return false;
    }

    /**
     * @param string      $memberId       Member Id
     * @param string      $siteId         SiteId Id
     * @param Site        $site           Site
     * @param string|null $subscriptionId Subscription Id
     *
     * @return MemberInfo
     */
    protected function retrieveMemberData(
        string $memberId,
        string $siteId,
        Site $site,
        ?string $subscriptionId
    ): MemberInfo {
        return $this->memberProfileGatewayService()->retrieveMemberProfile(
            $memberId,
            $siteId,
            $site->publicKeys()[0],
            (string) $this->requestSession()->value(),
            $subscriptionId
        );
    }

    /**
     * @param string $siteId The site id
     *
     * @return Site
     * @throws \Exception
     */
    protected function retrieveSite(string $siteId): Site
    {
        return $this->configServiceClient->getSite($siteId);
    }

    /**
     * @param Site              $site                   Site
     * @param PurchaseProcessed $purchaseProcessedEvent Purchase Processed event
     *
     * @param array|null        $crossSalePurchaseData
     *
     * @return MemberInfo
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoUsername
     * @throws LoggerException
     * @throws Exception
     */
    protected function buildMemberInfo(
        Site $site,
        PurchaseProcessed $purchaseProcessedEvent,
        ?array $crossSalePurchaseData = null
    ): MemberInfo {
        $memberDataFromEvent = $purchaseProcessedEvent->memberInfo();

        // Always set the username from subscription username
        if(!empty($crossSalePurchaseData)) {
            $username = $crossSalePurchaseData['subscriptionUsername'] ?? (isset($memberDataFromEvent['username']) ? $memberDataFromEvent['username'] : null);
        } else {
            $username = $purchaseProcessedEvent->subscriptionUsername() ?? (isset($memberDataFromEvent['username']) ? $memberDataFromEvent['username'] : null);
        }

        if (!is_null($memberDataFromEvent) && !$purchaseProcessedEvent->isExistingMember()) {
            $email = env('EMAIL_SERVICE_TEST_DESTINATION') ?: $memberDataFromEvent['email'];

            return MemberInfo::create(
                MemberId::createFromString($purchaseProcessedEvent->memberId()),
                Email::create($email),
                !empty($username) ? Username::create($username) : null,
                $memberDataFromEvent['firstName'] ?? null,
                $memberDataFromEvent['lastName'] ?? null
            );
        }

        $memberInfo = $this->retrieveMemberData(
            $purchaseProcessedEvent->memberId(),
            (string) $site->siteId(),
            $site,
            $purchaseProcessedEvent->subscriptionId()
        );

        if (!empty($username)) {
            $memberInfo->setUsername(Username::create($username));
        }

        return $memberInfo;
    }

    /**
     * @param EmailSettings $emailSettings
     *
     * @return string
     */
    protected function getSubject(EmailSettings $emailSettings): string
    {
        if ($emailSettings->getSubject() && !empty($emailSettings->getSubject()->getValue())) {
            return $emailSettings->getSubject()->getValue();
        }
        return self::EMAIL_SUBJECT;
    }

    /**
     * @param EmailSettings $emailSettings
     *
     * @return string|null
     */
    protected function getFriendlyName(EmailSettings $emailSettings): ?string
    {
        if ($emailSettings->getFriendlyName()) {
            return $emailSettings->getFriendlyName()->getValue();
        }
        return null;
    }

    /**
     * @param EmailSettings $emailSettings Email settings
     *
     * @return string
     */
    protected function getFrom(EmailSettings $emailSettings): string
    {
        if ($emailSettings->getFrom() && !empty($emailSettings->getFrom()->getValue())) {
            return $emailSettings->getFrom()->getValue();
        }
        return self::DEFAULT_FROM;
    }
}
