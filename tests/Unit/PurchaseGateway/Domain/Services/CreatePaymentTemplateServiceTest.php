<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ProbillerNG\PaymentTemplateServiceClient\Api\PaymentTemplateCommandsApi;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePaymentTemplateAsyncService;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\CreatePaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NetbillingCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCheckRetrieveTransactionResult;
use Tests\UnitTestCase;

class CreatePaymentTemplateServiceTest extends UnitTestCase
{
    /**
     * @var MockObject|PaymentTemplateCommandsApi
     */
    private $paymenteTemplateMockApi;

    /**
     * @var MockObject|TransactionService
     */
    private $transactionServiceMockedApi;
    /**
     * @var MockObject|RocketgateCCRetrieveTransactionResult
     */
    private $mockedTransactionResult;
    /**
     * @var MockObject|NewCCTransactionInformation
     */
    private $ccTransactionInformation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymenteTemplateMockApi     = $this->createMock(PaymentTemplateCommandsApi::class);
        $this->transactionServiceMockedApi = $this->createMock(TransactionService::class);

        $this->ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $this->ccTransactionInformation->method('first6')->willReturn('123456');
        $this->ccTransactionInformation->method('last4')->willReturn('1234');
        $this->ccTransactionInformation->method('cardExpirationMonth')->willReturn(12);
        $this->ccTransactionInformation->method('cardExpirationYear')->willReturn(2023);
        $this->ccTransactionInformation->method('status')->willReturn('approved');
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_call_create_payment_template_when_rocketgate_cc_transaction_returned(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(1))
            ->method('createPaymentTemplate');

        $this->mockedTransactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('rocketgate');
        $this->mockedTransactionResult->method('cardHash')->willReturn('cardHash');
        $this->mockedTransactionResult->method('customerId')->willReturn('customerId');

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($this->ccTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            $this->faker->uuid,
            TransactionId::create(),
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_call_create_payment_template_when_netbilling_cc_transaction_returned(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(1))
            ->method('createPaymentTemplate');


        $billerFields = $this->createMock(NetbillingBillerFields::class);
        $billerFields->method('binRouting')->willReturn('123456');

        $this->mockedTransactionResult = $this->createMock(NetbillingCCRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('netbilling');
        $this->mockedTransactionResult->method('cardHash')->willReturn($this->netbillingCardHash());
        $this->mockedTransactionResult->method('billerFields')->willReturn($billerFields);

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($this->ccTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            $this->faker->uuid,
            TransactionId::create(),
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_not_create_payment_template_when_it_is_not_a_cc_transaction(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(0))
            ->method('createPaymentTemplate');

        $checkTransactionInformation = $this->createMock(CheckTransactionInformation::class);

        $this->mockedTransactionResult = $this->createMock(RocketgateCheckRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('rocketgate');

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($checkTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            $this->faker->uuid,
            TransactionId::create(),
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_not_create_payment_template_when_it_is_not_a_rocketgate_or_netbilling_transaction(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(0))
            ->method('createPaymentTemplate');

        $this->mockedTransactionResult = $this->createMock(QyssoRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('qysso');

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($this->ccTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            $this->faker->uuid,
            TransactionId::create(),
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_not_create_payment_template_when_rocketgate_cc_transaction_returned_is_not_approved(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(0))
            ->method('createPaymentTemplate');

        $this->mockedTransactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('rocketgate');
        $this->mockedTransactionResult->method('cardHash')->willReturn('cardHash');
        $this->mockedTransactionResult->method('customerId')->willReturn('customerId');

        $declinedCCTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $declinedCCTransactionInformation->method('first6')->willReturn('123456');
        $declinedCCTransactionInformation->method('last4')->willReturn('1234');
        $declinedCCTransactionInformation->method('cardExpirationMonth')->willReturn(12);
        $declinedCCTransactionInformation->method('cardExpirationYear')->willReturn(2023);
        $declinedCCTransactionInformation->method('status')->willReturn('declined');

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($declinedCCTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            $this->faker->uuid,
            TransactionId::create(),
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_create_payment_template_when_rocketgate_cc_transaction_returned_is_declined_with_nsf_and_nsf_enabled_on_site(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(1))
            ->method('createPaymentTemplate');

        $this->mockedTransactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('rocketgate');
        $this->mockedTransactionResult->method('cardHash')->willReturn('cardHash');
        $this->mockedTransactionResult->method('customerId')->willReturn('customerId');

        $declinedCCTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $declinedCCTransactionInformation->method('first6')->willReturn('123456');
        $declinedCCTransactionInformation->method('last4')->willReturn('1234');
        $declinedCCTransactionInformation->method('cardExpirationMonth')->willReturn(12);
        $declinedCCTransactionInformation->method('cardExpirationYear')->willReturn(2023);
        $declinedCCTransactionInformation->method('status')->willReturn('declined');

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($declinedCCTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTemplateService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTemplateService->addPaymentTemplate(
            $this->faker->uuid,
            TransactionId::create(),
            true
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_not_call_create_payment_template_when_transaction_id_is_null(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(0))
            ->method('createPaymentTemplate');

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            $this->faker->uuid,
            null,
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_not_call_create_payment_template_when_member_id_is_null(): void
    {
        $this->paymenteTemplateMockApi->expects($this->exactly(0))
            ->method('createPaymentTemplate');

        $createPaymentTemplateAsync = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTempalteService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsync
        );

        $createPaymentTempalteService->addPaymentTemplate(
            null,
            TransactionId::create(),
            false
        );
    }

    /**
     * @return string
     */
    private function netbillingCardHash(): string
    {
        return base64_encode("CS:113832223893".(string) $this->faker->randomNumber(4));
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     */
    public function it_should_call_create_async_event_service_to_create_payment_template_async_event(): void
    {
        $this->mockedTransactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $this->mockedTransactionResult->method('billerName')->willReturn('rocketgate');
        $this->mockedTransactionResult->method('cardHash')->willReturn('cardHash');
        $this->mockedTransactionResult->method('customerId')->willReturn('customerId');

        $approvedCCTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $approvedCCTransactionInformation->method('first6')->willReturn('123456');
        $approvedCCTransactionInformation->method('last4')->willReturn('1234');
        $approvedCCTransactionInformation->method('cardExpirationMonth')->willReturn(12);
        $approvedCCTransactionInformation->method('cardExpirationYear')->willReturn(2030);
        $approvedCCTransactionInformation->method('status')->willReturn('approved');

        $this->mockedTransactionResult->method('transactionInformation')->willReturn($approvedCCTransactionInformation);
        $this->transactionServiceMockedApi->method('getTransactionDataBy')->willReturn($this->mockedTransactionResult);

        $createPaymentTemplateAsyncService = $this->createMock(CreatePaymentTemplateAsyncService::class);

        $createPaymentTemplateService = new CreatePaymentTemplateService(
            $this->paymenteTemplateMockApi,
            $this->transactionServiceMockedApi,
            $createPaymentTemplateAsyncService
        );

        $createPaymentTemplateAsyncService->expects($this->once())->method('create');

        $createPaymentTemplateService->createPaymentTemplateAsyncEvent(
            TransactionId::createFromString($this->faker->uuid),
            $this->faker->uuid,
            $this->faker->uuid
        );
    }
}
