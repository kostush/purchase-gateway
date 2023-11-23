<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\ThirdPartyRedirect;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect\ThirdPartyRedirectQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect\ThirdPartyRedirectQuery;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect\ThirdPartyRedirectQueryHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\UnitTestCase;

class ThirdPartyRedirectQueryHandlerTest extends UnitTestCase
{
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



    /** @var ThirdPartyRedirectQueryDTOAssembler|MockObject */
    private $assembler;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configServiceClient  = $this->createMock(ConfigService::class);
        $this->billerMappingService = $this->createMock(BillerMappingService::class);
        $this->transactionService   = $this->createMock(TransactionService::class);

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchase              = $this->createMock(Purchase::class);
        $this->purchaseProcess->method('purchase')->willReturn($purchase);

        $this->processHandler = $this->createMock(PurchaseProcessHandler::class);
        $this->biServiceMock  = $this->createMock(BILoggerService::class);
        $this->query          = new ThirdPartyRedirectQuery($this->faker->uuid);


        $transaction = $this->createMock(Transaction::class);
        $transaction->method('redirectUrl')->willReturn($this->faker->url);

        $collection = new TransactionCollection();
        $collection->add($transaction);

        $this->assembler = $this->createMock(ThirdPartyRedirectQueryDTOAssembler::class);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_throw_session_already_processed_exception_when_state_not_pending(): void
    {
        $this->expectException(SessionAlreadyProcessedException::class);

        $this->purchaseProcess->method('isValid')->willReturn(false);
        $this->purchaseProcess->method('redirectUrl')->willReturn($this->faker->url);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new ThirdPartyRedirectQueryHandler(
            $this->assembler,
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
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_given_non_existing_process_purchase_session(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->processHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $handler = new ThirdPartyRedirectQueryHandler(
            $this->assembler,
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
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_missing_redirect_url_on_redirect(): void
    {
        $this->expectException(MissingRedirectUrlException::class);

        $this->processHandler->method('load')->willReturn(
            $this->purchaseProcess
        );

        $handler = new ThirdPartyRedirectQueryHandler(
            $this->assembler,
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
}
