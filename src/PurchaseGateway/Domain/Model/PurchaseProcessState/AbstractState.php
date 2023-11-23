<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\StateRestoreException;

class AbstractState implements State
{
    /**
     * @return AbstractState
     * @throws IllegalStateTransitionException
     */
    public static function create(): AbstractState
    {
        return new static();
    }

    /**
     * @return bool
     */
    public function created(): bool
    {
        return $this instanceof Created;
    }

    /**
     * @return bool
     */
    public function blockedDueToFraudAdvice(): bool
    {
        return $this instanceof BlockedDueToFraudAdvice;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this instanceof Valid;
    }

    /**
     * @return bool
     */
    public function processing(): bool
    {
        return $this instanceof Processing;
    }

    /**
     * @return bool
     */
    public function pending(): bool
    {
        return $this instanceof Pending;
    }

    /**
     * @return bool
     */
    public function threeDAuthenticated(): bool
    {
        return $this instanceof ThreeDAuthenticated;
    }

    /**
     * @return bool
     */
    public function threeDLookupPerformed(): bool
    {
        return $this instanceof ThreeDLookupPerformed;
    }

    /**
     * @return bool
     */
    public function redirected(): bool
    {
        return $this instanceof Redirected;
    }

    /**
     * @return bool
     */
    public function cascadeBillersExhausted(): bool
    {
        return $this instanceof CascadeBillersExhausted;
    }

    /**
     * @return bool
     */
    public function processed(): bool
    {
        return $this instanceof Processed;
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function blockDueToFraudAdvice()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function validate()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function startPending()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function authenticateThreeD()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function performThreeDLookup()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function redirect()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws Exception
     * @throws \Exception
     * @return void
     */
    public function startProcessing()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws \Exception
     * @return void
     */
    public function finishProcessing()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @throws IllegalStateTransitionException
     * @throws \Exception
     * @return void
     */
    public function noMoreBillersAvailable()
    {
        throw new IllegalStateTransitionException();
    }

    /**
     * @return string
     */
    public static function name(): string
    {
        return strtolower(class_basename(static::class));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::name();
    }

    /**
     * @param string $stateName State Name
     * @return AbstractState
     * @throws IllegalStateTransitionException
     * @throws \Exception
     */
    public static function restore(string $stateName): AbstractState
    {
        switch ($stateName) {
            case Created::name():
                return Created::create();
            case BlockedDueToFraudAdvice::name():
                return BlockedDueToFraudAdvice::create();
            case Valid::name():
                return Valid::create();
            case Processing::name():
                return Processing::create();
            case Pending::name():
                return Pending::create();
            case Processed::name():
                return Processed::create();
            case ThreeDAuthenticated::name():
                return ThreeDAuthenticated::create();
            case ThreeDLookupPerformed::name():
                return ThreeDLookupPerformed::create();
            case Redirected::name():
                return Redirected::create();
            default:
                throw new StateRestoreException();
        }
    }
}
