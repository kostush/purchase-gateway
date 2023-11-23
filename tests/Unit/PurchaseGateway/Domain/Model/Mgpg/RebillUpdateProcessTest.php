<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model\Mgpg;

use ProbillerMGPG\SubsequentOperations\Common\NextAction;
use ProbillerMGPG\SubsequentOperations\Process\ProcessResponse;
use ProbillerMGPG\SubsequentOperations\Process\Response\Charge;
use ProbillerMGPG\SubsequentOperations\Process\Response\Invoice;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgSubsequentOperationResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessRebillUpdateCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\RebillUpdateProcess;
use Tests\UnitTestCase;

class RebillUpdateProcessTest extends UnitTestCase
{

    /**
     * @test
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function it_should_return_success_process_when_invoice_charge_status_is_success(): void
    {
        //given
        $command = $this->createMock(ProcessRebillUpdateCommand::class);
        $command->method('getNgSessionId')->willReturn($this->faker->uuid);
        $command->method('getPublicKeyId')->willReturn(1);

        //when
        $chargeStatus = 'success';

        $response             = new ProcessResponse();
        $response->invoice    = $this->buildInvoice($chargeStatus);
        $response->nextAction = $this->buildNextAction();

        $rebillUpdateProcess = new RebillUpdateProcess(new MgpgSubsequentOperationResponseService());
        $rebillUpdateProcess->create($response, $command);

        //then
        $this->assertTrue($rebillUpdateProcess->success());
    }

    /**
     * @test
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function it_should_return_false_process_when_invoice_charge_status_is_other_than_success(): void
    {
        //given
        $command = $this->createMock(ProcessRebillUpdateCommand::class);
        $command->method('getNgSessionId')->willReturn($this->faker->uuid);
        $command->method('getPublicKeyId')->willReturn(1);

        //when
        $chargeStatusSuccess = 'declined';

        $response             = new ProcessResponse();
        $response->invoice    = $this->buildInvoice($chargeStatusSuccess);
        $response->nextAction = $this->buildNextAction();

        $rebillUpdateProcess = new RebillUpdateProcess(new MgpgSubsequentOperationResponseService());
        $rebillUpdateProcess->create($response, $command);

        //then
        $this->assertFalse($rebillUpdateProcess->success());
    }

    /**
     * @test
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function it_should_return_false_process_when_invoice_is_empty(): void
    {
        //given
        $command = $this->createMock(ProcessRebillUpdateCommand::class);
        $command->method('getNgSessionId')->willReturn($this->faker->uuid);
        $command->method('getPublicKeyId')->willReturn(1);

        $response             = new ProcessResponse();
        $response->invoice    = null;
        $response->nextAction = $this->buildNextAction();

        $rebillUpdateProcess = new RebillUpdateProcess(new MgpgSubsequentOperationResponseService());
        $rebillUpdateProcess->create($response, $command);

        //then
        $this->assertFalse($rebillUpdateProcess->success());
    }

    /**
     * @param string $chargeStatusSuccess
     * @return Invoice
     */
    private function buildInvoice(string $chargeStatusSuccess): Invoice
    {
        $charge = new Charge();

        $charge->status = $chargeStatusSuccess;

        $invoice            = new Invoice();
        $invoice->charges[] = $charge;

        return $invoice;
    }

    /**
     * @return NextAction
     */
    private function buildNextAction(): NextAction
    {
        $nextAction = new NextAction();
        $nextAction->type = 'teste';
        $nextAction->reason = 'noReason';
        return $nextAction;
    }
}
