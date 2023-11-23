<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\ThirdPartyPostback;

use ProBillerNG\BI\BILoggerService;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback\ThirdPartyPostbackCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Domain\Services\UserInfoService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\UnitTestCase;

class ThirdPartyPostbackCommandHandlerTest extends UnitTestCase
{
    /**
     * @var PurchaseProcessHandler
     */
    private $purchaseProcessHandler;

    /**
     * @var ThirdPartyPostbackCommand
     */
    private $postbackCommand;

    /**
     * @var ThirdPartyPostbackCommandDTOAssembler
     */
    private $postbackHttpCommandDTOAssembler;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var PurchaseService
     */
    private $purchaseService;

    /**
     * @var MockObject|UserInfoService
     */
    private $userInfoService;

    /**
     * @var BILoggerService;
     */
    private $biLoggerService;

    /**
     * @var ConfigService
     */
    private $configService;

    /**
     * @var PostbackService
     */
    private $postbackService;

    /**
     * @var TokenGenerator
     */
    private $tockenGenerator;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var EventIngestionService
     */
    private $eventIngestion;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcessHandler          = $this->createMock(PurchaseProcessHandler::class);
        $this->postbackCommand                 = $this->createMock(ThirdPartyPostbackCommand::class);
        $this->postbackHttpCommandDTOAssembler = $this->createMock(ThirdPartyPostbackCommandDTOAssembler::class);
        $this->transactionService              = $this->createMock(TransactionService::class);
        $this->purchaseService                 = $this->createMock(PurchaseService::class);
        $this->userInfoService                 = $this->createMock(UserInfoService::class);
        $this->biLoggerService                 = $this->createMock(BILoggerService::class);
        $this->configService                   = $this->createMock(ConfigService::class);
        $this->postbackService                 = $this->createMock(PostbackService::class);
        $this->tockenGenerator                 = $this->createMock(TokenGenerator::class);
        $this->cryptService                    = $this->createMock(CryptService::class);
        $this->eventIngestion                  = $this->createMock(EventIngestionService::class);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws SessionNotFoundException
     * @throws InvalidCommandException
     * @throws InvalidUUIDException
     */
    public function it_should_throw_session_not_found_exception_when_session_is_not_found(): void
    {
        $this->expectException(SessionNotFoundException::class);

        $this->purchaseProcessHandler->method('load')->willThrowException(new InitPurchaseInfoNotFoundException());

        $postbackCommandHandler = new ThirdPartyPostbackCommandHandler(
            $this->postbackHttpCommandDTOAssembler,
            $this->purchaseProcessHandler,
            $this->transactionService,
            $this->purchaseService,
            $this->userInfoService,
            $this->biLoggerService,
            $this->configService,
            $this->postbackService,
            $this->tockenGenerator,
            $this->cryptService,
            $this->eventIngestion

        );

        $postbackCommandHandler->execute($this->postbackCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws SessionNotFoundException
     * @throws InvalidCommandException
     * @throws InvalidUUIDException
     */
    public function it_should_throw_transaction_not_found_exception_when_transaction_id_does_not_exist_in_purchase_process(): void
    {
        $this->expectException(TransactionNotFoundException::class);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('isProcessed')->willReturn(true);

        $this->purchaseProcessHandler->method('load')->willReturn($purchaseProcess);

        $postbackCommandHandler = new ThirdPartyPostbackCommandHandler(
            $this->postbackHttpCommandDTOAssembler,
            $this->purchaseProcessHandler,
            $this->transactionService,
            $this->purchaseService,
            $this->userInfoService,
            $this->biLoggerService,
            $this->configService,
            $this->postbackService,
            $this->tockenGenerator,
            $this->cryptService,
            $this->eventIngestion
        );

        $postbackCommandHandler->execute($this->postbackCommand);
    }
}
