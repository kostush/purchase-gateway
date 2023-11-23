<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerAvailablePaymentMethods;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RestartProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Exception;
use Throwable;

class ThirdPartyPaymentProcessCommandHandler extends BasePaymentProcessCommandHandler
{
    /**
     * NewPaymentProcessCommandHandler constructor.
     * @param SessionHandler              $purchaseProcessHandler SessionHandler
     * @param ProcessPurchaseDTOAssembler $dtoAssembler           ProcessPurchaseDTOAssembler
     */
    public function __construct(
        SessionHandler $purchaseProcessHandler,
        ProcessPurchaseDTOAssembler $dtoAssembler
    ) {
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->dtoAssembler           = $dtoAssembler;
    }

    /**
     * @param Command $command The command
     * @return ProcessPurchaseHttpDTO
     * @throws Exception
     * @throws InvalidCommandException
     * @throws LoggerException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws IllegalStateTransitionException
     * @throws Throwable
     */
    public function execute(Command $command)
    {
        if (!$command instanceof ProcessPurchaseCommand) {
            throw new InvalidCommandException(ProcessPurchaseCommand::class, $command);
        }

        // retrieve purchase process
        $this->purchaseProcess = $this->purchaseProcessHandler->load($command->sessionId());

        try {
            $this->checkReturnUrl();
            $this->checkIfPurchaseHasBeenAlreadyProcessed();

            if ($this->purchaseProcess->wasMemberIdGenerated()
                || $this->purchaseProcess->cascade()->currentBiller() instanceof BillerAvailablePaymentMethods
            ) {
                $this->addUserInfoToPurchaseProcess($command);
            } else {
                $this->purchaseProcess->userInfo()->setUsername(Username::create($command->username()));
                $this->purchaseProcess->userInfo()->setPassword(Password::create($command->password()));
            }

            $this->setPaymentInfo($command);

            $dto = $this->dtoAssembler->assemble($this->purchaseProcess);

            $this->purchaseProcess->startPending();

            // Return DTO
            return $dto;
        } catch (Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::logException($e);
            throw $e;
        } finally {
            // Store the purchase process
            $this->purchaseProcessHandler->update($this->purchaseProcess);
        }
    }

    /**
     * @param ProcessPurchaseCommand $command The purchase command
     * @return void
     * @throws LoggerException
     * @throws \Throwable
     */
    protected function addUserInfoToPurchaseProcess(ProcessPurchaseCommand $command): void
    {
        try {
            if (empty($command->country())) {
                throw new InvalidUserInfoCountry();
            }

            $this->purchaseProcess->userInfo()->setCountryCode(CountryCode::create($command->country()));
        } catch (\Throwable $e) {
            throw $e;
        }

        parent::addUserInfoToPurchaseProcess($command);
    }

    /**
     * @param ProcessPurchaseCommand $command The process command
     * @return void
     * @throws LoggerException
     * @throws Throwable
     */
    protected function setPaymentInfo(ProcessPurchaseCommand $command): void
    {
        Log::info('Setting the payment info type direct debit');

        $paymentInfo = OtherPaymentTypeInfo::build(
            $this->purchaseProcess->paymentInfo()->paymentType(),
            $command->paymentMethod()
        );

        $this->purchaseProcess->setPaymentInfo($paymentInfo);
    }

    /**
     * @return void
     * @throws LoggerException
     * @throws SessionAlreadyProcessedException
     */
    protected function checkIfPurchaseHasBeenAlreadyProcessed(): void
    {
        if (!$this->purchaseProcess->isValid()) {
            throw new SessionAlreadyProcessedException(
                (string) $this->purchaseProcess->sessionId(),
                RestartProcess::create()->toArray(),
                $this->purchaseProcess->redirectUrl()
            );
        }
    }

    /**
     * @return void
     * @throws LoggerException
     * @throws MissingRedirectUrlException
     */
    protected function checkReturnUrl(): void
    {
        if (empty($this->purchaseProcess->redirectUrl())) {
            throw new MissingRedirectUrlException(
                RestartProcess::create()->toArray()
            );
        }
    }

    /**
     * @return BaseEvent
     */
    protected function generatePurchaseBiEvent(): BaseEvent
    {
        // TODO: Implement generatePurchaseBiEvent() method.
    }
}
