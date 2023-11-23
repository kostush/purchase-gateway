<?php

namespace ProBillerNG\PurchaseGateway;

use ProBillerNG\Logger\Log;

abstract class Exception extends \Exception
{
    /**
     * @var int $code
     */
    protected $code = Code::PURCHASE_GATEWAY_EXCEPTION;

    /** @var array */
    protected $nextAction = [];

    /**
     * Exception constructor.
     *
     * @param \Throwable|null $previous Previews Error
     * @param array           ...$args  Other parameters
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(?\Throwable $previous = null, ...$args)
    {
        $message = $this->buildMessage($args);

        parent::__construct($message, $this->code, $previous);

        // Log previous exception
        if (null !== $previous) {
            Log::error('Previous exception', ['message' => $previous->getMessage(), 'code' => $previous->getCode()]);
        }

        // Log current exception
        Log::logException($this);
    }

    /**
     * @return array
     */
    public function nextAction(): array
    {
        return $this->nextAction;
    }

    /**
     * @param array $args Argument Array
     * @return string
     */
    private function buildMessage($args): string
    {
        $message = Code::getMessage($this->code);

        if (!empty($args)) {
            $difference = count($args) - substr_count($message, '%');

            if ($difference > 0) {
                $message .= ' - Info:' . str_repeat(' [%s],', $difference);
                $message  = rtrim($message, ',');
            }

            $message = \sprintf($message, ...$args);
        }

        return $message;
    }
}
