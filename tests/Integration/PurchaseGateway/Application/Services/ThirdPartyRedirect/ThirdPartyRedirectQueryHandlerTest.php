<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\ThirdPartyRedirect;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn\ReturnHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect\ThirdPartyRedirectQuery;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect\ThirdPartyRedirectQueryHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn\ReturnCommand;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;
use Tests\IntegrationTestCase;

class ThirdPartyRedirectQueryHandlerTest extends IntegrationTestCase
{
    public const EMAIL      = 'email@mindgeek.com';
    public const FIRST_NAME = 'firstname';
    public const LAST_NAME  = 'lastname';

    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var PurchaseProcessHandler
     */
    private $processHandler;

    /**
     * @var ThirdPartyRedirectQuery
     */
    private $query;

    /**
     * @var BILoggerService
     */
    private $biServiceMock;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * @var BillerMappingService
     */
    private $billerMappingService;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var JsonWebTokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var SodiumCryptService
     */
    private $cryptService;

    /**
     * @return void
     * @throws \ProBillerNG\Crypt\Sodium\InvalidPrivateKeySizeException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess      = $this->createMock(PurchaseProcess::class);
        $this->processHandler       = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock        = $this->createMock(BILoggerService::class);
        $this->configServiceClient  = $this->createMock(ConfigService::class);
        $this->billerMappingService = $this->createMock(BillerMappingService::class);
        $this->transactionService   = $this->createMock(TransactionService::class);

        $this->tokenGenerator = new JsonWebTokenGenerator();
        $this->cryptService   = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );


        $this->configServiceClient->method('getSite')->willReturn($this->createSite());
        $this->query = new ThirdPartyRedirectQuery($this->faker->uuid);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_successfully_execute_query_and_return_redirect_response(): void
    {
        $purchaseProcess           = $this->createMock(PurchaseProcess::class);
        $purchase                  = $this->createMock(Purchase::class);
        $paymentTemplateCollection = $this->createMock(PaymentTemplateCollection::class);
        $paymentTemplate           = $this->createMock(PaymentTemplate::class);

        $paymentTemplate->method('templateId')->willReturn($this->faker->uuid);

        $paymentTemplateCollection->method('getLastUsedBillerTemplate')->willReturn($paymentTemplate);

        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('isPending')->willReturn(true);
        $purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);

        $purchaseProcess->method('paymentInfo')->willReturn(
            CCPaymentInfo::build('cc', null)
        );
        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new EpochBiller()
                    ]
                ),
                new EpochBiller()
            )
        );
        $purchaseProcess->method('paymentTemplateCollection')->willReturn($paymentTemplateCollection);

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn(
            $purchaseProcess
        );

        $transaction = $this->createMock(Transaction::class);

        $transaction->method('state')->willReturn(Transaction::STATUS_PENDING);
        $transaction->method('redirectUrl')->willReturn($this->faker->url);
        $collection = new TransactionCollection();
        $collection->add($transaction);

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $initItem->method('wasItemPurchasePending')->willReturn(true);
        $initItem->method('lastTransaction')->willReturn($transaction);

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);

        $handler = new ThirdPartyRedirectQueryHandler(
            new ThirdPartyRedirectQueryDTOAssembler(
                $this->tokenGenerator,
                $this->cryptService
            ),
            $processHandler,
            $this->billerMappingService,
            $this->configServiceClient,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(PaymentTemplateService::class),
            $this->createMock(MemberProfileGatewayService::class)
        );

        /** @var ThirdPartyRedirectHttpDTO $result */
        $result = $handler->execute($this->query);

        $this->assertInstanceOf(ThirdPartyRedirectHttpDTO::class, $result);
    }

    /**
     * @test
     * @return UserInfo
     * @throws \Exception
     */
    public function it_should_not_update_email_from_user_info_if_biller_is_instance_of_biller_available_payment_methods(): UserInfo
    {
        $userInfo = UserInfo::create(
            CountryCode::create($this->faker->countryCode),
            Ip::create($this->faker->ipv4),
            Email::create(self::EMAIL),
            Username::create('username'),
            Password::create('password'),
            FirstName::create(self::FIRST_NAME),
            LastName::create(self::LAST_NAME)
        );

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchase        = $this->createMock(Purchase::class);

        $purchaseProcess->method('purchase')->willReturn($purchase);
        $purchaseProcess->method('isPending')->willReturn(true);
        $purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);
        $purchaseProcess->method('userInfo')->willReturn($userInfo);

        $purchaseProcess->method('paymentInfo')->willReturn(
            CCPaymentInfo::build('cc', null)
        );
        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new QyssoBiller()
                    ]
                ),
                new QyssoBiller()
            )
        );

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('load')->willReturn(
            $purchaseProcess
        );

        $transaction = $this->createMock(Transaction::class);

        $transaction->method('state')->willReturn(Transaction::STATUS_PENDING);
        $transaction->method('redirectUrl')->willReturn($this->faker->url);
        $collection = new TransactionCollection();
        $collection->add($transaction);

        $initItem = $this->createMock(InitializedItem::class);
        $initItem->method('transactionCollection')->willReturn($collection);
        $initItem->method('wasItemPurchasePending')->willReturn(true);
        $initItem->method('lastTransaction')->willReturn($transaction);

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initItem);

        $handler = new ThirdPartyRedirectQueryHandler(
            new ThirdPartyRedirectQueryDTOAssembler(
                $this->tokenGenerator,
                $this->cryptService
            ),
            $processHandler,
            $this->billerMappingService,
            $this->configServiceClient,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(PaymentTemplateService::class),
            $this->createMock(MemberProfileGatewayService::class)
        );

        /** @var ThirdPartyRedirectHttpDTO $result */
        $handler->execute($this->query);

        $this->assertSame(self::EMAIL, (string) $userInfo->email());

        return $userInfo;
    }

    /**
     * @test
     * @depends it_should_not_update_email_from_user_info_if_biller_is_instance_of_biller_available_payment_methods
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_containt_corect_first_name(UserInfo $userInfo): void
    {
        $this->assertSame(self::FIRST_NAME, (string) $userInfo->firstName());
    }

    /**
     * @test
     * @depends it_should_not_update_email_from_user_info_if_biller_is_instance_of_biller_available_payment_methods
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_containt_corect_last_name(UserInfo $userInfo): void
    {
        $this->assertSame(self::LAST_NAME, (string) $userInfo->lastName());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_given_already_processed_purchase_session(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $this->purchaseProcess->method('redirectUrl')->willReturn('dummy.url');

        $handler = new ThirdPartyRedirectQueryHandler(
            $this->createMock(ThirdPartyRedirectQueryDTOAssembler::class),
            $this->processHandler,
            $this->billerMappingService,
            $this->configServiceClient,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(PaymentTemplateService::class),
            $this->createMock(MemberProfileGatewayService::class)
        );

        $handler->execute($this->query);
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_exception_given_non_existing_process_purchase_session(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->processHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $handler = new ThirdPartyRedirectQueryHandler(
            $this->createMock(ThirdPartyRedirectQueryDTOAssembler::class),
            $this->processHandler,
            $this->billerMappingService,
            $this->configServiceClient,
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(PaymentTemplateService::class),
            $this->createMock(MemberProfileGatewayService::class)
        );

        $handler->execute($this->query);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Throwable
     * @return void
     */
    public function it_should_throw_transaction_already_processed_exception_when_transaction_service_return_transaction_already_processed_in_redirect(): void
    {
        $this->expectException(TransactionAlreadyProcessedException::class);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->faker->uuid;
        $mainItem['addonId']        = $this->faker->uuid;
        $mainItem['bundleId']       = $this->faker->uuid;
        $mainItem['isCrossSale']    = false;

        $sessionPayload['initializedItemCollection'] = [$mainItem];
        $sessionPayload['state']                     = 'redirected';
        $sessionPayload['redirectUrl']               = $this->faker->url;

        $transactionId = $sessionPayload['initializedItemCollection'][0]['transactionCollection'][0]['transactionId'];

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $this->purchaseProcess->method('redirectUrl')->willReturn('dummy.url');
        $this->purchaseProcess->method('isPending')
            ->willThrowException(
                new \ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException()
            );

        $transaction            = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_PENDING);
        $transactionInformation->method('transactionId')->willReturn($transactionId);

        $transaction->method('transactionInformation')->willReturn($transactionInformation);
        $transaction->method('transactionInformation')
            ->willReturn(EpochCCRetrieveTransactionResult::class);

        $this->transactionService->method('getTransactionDataBy')->willReturn($transaction);
        $this->transactionService->method('addBillerInteraction')
            ->willThrowException(
                new \ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException()
            );

        $handler = new ThirdPartyRedirectQueryHandler(
            $this->createMock(ThirdPartyRedirectQueryDTOAssembler::class),
            $this->processHandler,
            $this->billerMappingService,
            $this->createMock(ConfigService::class),
            $this->transactionService,
            $this->biServiceMock,
            $this->createMock(CryptService::class),
            $this->createMock(TokenGenerator::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(PaymentTemplateService::class),
            $this->createMock(MemberProfileGatewayService::class)
        );

        $handler->execute($this->query);
    }

}
