<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Mgpg;

use ProbillerMGPG\SubsequentOperations\Common\NextAction;
use ProbillerMGPG\SubsequentOperations\Process\ProcessResponse;
use ProbillerMGPG\SubsequentOperations\Process\Response\Invoice;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgSubsequentOperationResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessRebillUpdateCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextAction as NGNextAction;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionProcessFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;

/**
 * Class PurchaseProcess This MGPG version provides an alternate version that is only concerned with what
 * is needed to correctly assemble a DTO since MGPG handles the state tracking and other details. For the
 * needs of the MGPG process endpoint, all the heavy-lifting is done on MGPG.
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg
 */
class RebillUpdateProcess extends GenericPurchaseProcess
{
    /**
     * @var NGNextAction
     */
    protected $nextAction;

    /**
     * @var MgpgResponseService
     */
    protected $mgpgResponseService;

    /**
     * @var ProcessPurchaseCommand
     */
    protected $command;

    /**
     * @var Invoice
     */
    private $invoice;

    /**
     * @var bool
     */
    private $success;

    /**
     * PurchaseProcess constructor.
     * @param MgpgSubsequentOperationResponseService $mgpgSubsequentOperationResponseService
     */
    public function __construct(
        MgpgSubsequentOperationResponseService $mgpgSubsequentOperationResponseService
    )
    {
        $this->mgpgResponseService = $mgpgSubsequentOperationResponseService;
    }

    /**
     * @param ProcessResponse $response
     * @param ProcessRebillUpdateCommand $command
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     * @throws Exception
     * @throws \Exception
     */
    public function create(
        ProcessResponse $response,
        ProcessRebillUpdateCommand $command
    ): void
    {
        Log::info('CreateRebillUpdateProcess Creating RebillUpdateProcess');
        $this->sessionId      = SessionId::createFromString($command->getNgSessionId());
        $this->command        = $command;
        $this->publicKeyIndex = $command->getPublicKeyId();
        $this->nextAction     = $this->createNextAction($response->nextAction);
        $this->fraudAdvice    = $this->createFraud($response->nextAction);
        $this->invoice        = $response->invoice;
        $this->success        = $this->initStatusFromInvoice($this->invoice);
    }

    /**
     * @param Invoice|null $invoice
     * @return bool
     * @throws Exception
     */
    private function initStatusFromInvoice(?Invoice $invoice): bool
    {
        if (!$invoice instanceof Invoice) {
            Log::info(
                'InitStatusFromInvoice Invoice not returned from mgpg.',
                ['success' => false]
            );
            return false;
        }

        if (empty($invoice->charges)) {
            Log::info(
                'InitStatusFromInvoice no charges found in Invoice to return status.',
                ['success' => false]
            );
            return false;
        }

        if ($invoice->charges[0]->status == 'success') {
            Log::info(
                'InitStatusFromInvoice charge success.',
                ['status' => $invoice->charges[0]->status, 'success' => true]
            );
            return true;
        }

        Log::info(
            'InitStatusFromInvoice charge fail',
            ['status' => $invoice->charges[0]->status, 'success' => false]
        );

        return false;
    }

    protected function createFraud(NextAction $nextAction): FraudAdvice
    {
        if ($this->mgpgResponseService->blockedDueToFraudAdvice($nextAction)) {
            Log::info('CreatingRebillUpdateProcess Setting fraud recommendation.');
            $this->setFraudRecommendation(
                FraudRecommendation::create(
                    $nextAction->reasonDetails->code,
                    $nextAction->reasonDetails->severity,
                    $nextAction->reasonDetails->message
                )
            );
        }

        return $this->mgpgResponseService->translateFraudAdviceProcessStepToNg($nextAction);
    }

    /**
     * @param NextAction $nextAction
     * @return NGNextAction NG NextAction Process
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidStateException
     */
    public function createNextAction(NextAction $nextAction): NGNextAction
    {
        return NextActionProcessFactory::create(
            $this->createNextActionState($nextAction),
            null,
            $this->getThirdParty($nextAction),
            $this->mgpgResponseService->isRedirectUrl($nextAction),
            null,
            null,
            null,
            $nextAction->resolution ?? null,
            $nextAction->reason ?? null
        );
    }

    /**
     * @param NextAction $nextAction
     * @return AbstractState
     * @throws IllegalStateTransitionException|Exception
     */
    protected function createNextActionState(NextAction $nextAction): AbstractState
    {
        if ($this->mgpgResponseService->isRedirectUrl($nextAction)
            || $this->mgpgResponseService->isRenderGateway($nextAction)) {
            Log::info(sprintf('CreatingNextActionState Create %s %s', $nextAction->type, Valid::class));
            return Valid::create();
        }

        Log::info('CreatingNextActionState Create '.Processed::class);
        return Processed::create();
    }

    /**
     * @param NextAction $nextAction MGPG NextAction
     * @return ThirdParty
     * @throws Exception
     */
    public function getThirdParty(NextAction $nextAction): ?ThirdParty
    {
        if (isset($nextAction->thirdParty) && $nextAction->thirdParty->url) {
            Log::info('GettingThirdParty Creating third party next action url.');
            return ThirdParty::create($nextAction->thirdParty->url);
        }

        return null;
    }

    /**
     * This method was inherited and it is not used on rebill update adaptor flow.
     * @return bool
     */
    public function isCurrentBillerAvailablePaymentsMethods(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function nextAction(): array
    {
        return $this->nextAction->toArray();
    }

    /**
     * @return string
     */
    public function mgpgSessionId(): string
    {
        return $this->command->getMgpgSessionId();
    }

    /**
     * @return string
     */
    public function correlationId()
    {
        return $this->command->getCorrelationId();
    }

    /**
     * @return mixed
     */
    public function invoice()
    {
        return $this->invoice;
    }

    /**
     * @return bool
     */
    public function success(): bool
    {
        return $this->success;
    }
}
