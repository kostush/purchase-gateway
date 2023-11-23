<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

/**
 * Class ErrorClassification
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 */
class ErrorClassification
{
    public const ERROR_TYPE_HARD = 'Hard';

    /**
     * @var string
     */
    protected $groupDecline;

    /**
     * @var string
     */
    protected $errorType;

    /**
     * @var string
     */
    protected $groupMessage;

    /**
     * @var string
     */
    protected $recommendedAction;

    /**
     * ErrorClassification constructor.
     *
     * @param string $groupDecline      Group decline
     * @param string $errorType         Error type.
     * @param string $groupMessage      Group message.
     * @param string $recommendedAction Recommended action.
     */
    public function __construct(
        string $groupDecline,
        string $errorType,
        string $groupMessage,
        string $recommendedAction
    ) {
        $this->groupDecline      = $groupDecline;
        $this->errorType         = $errorType;
        $this->groupMessage      = $groupMessage;
        $this->recommendedAction = $recommendedAction;
    }

    /**
     * @return string
     */
    public function groupDecline(): string
    {
        return $this->groupDecline;
    }

    /**
     * @return string
     */
    public function errorType(): string
    {
        return $this->errorType;
    }

    /**
     * @return string
     */
    public function groupMessage(): string
    {
        return $this->groupMessage;
    }

    /**
     * @return string
     */
    public function recommendedAction(): string
    {
        return $this->recommendedAction;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'groupDecline'      => $this->groupDecline(),
            'errorType'         => $this->errorType(),
            'groupMessage'      => $this->groupMessage(),
            'recommendedAction' => $this->recommendedAction(),
        ];
    }
}
