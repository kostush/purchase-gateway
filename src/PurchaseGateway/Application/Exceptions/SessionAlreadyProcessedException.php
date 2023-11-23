<?php

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class SessionAlreadyProcessedException extends Exception
{
    /** @var int $code Error code */
    protected $code = Code::SESSION_ALREADY_PROCESSES;

    /** @var string */
    protected $returnUrl;

    /**
     * SessionAlreadyProcessedException constructor.
     * @param string $sessionId  Session id
     * @param array  $nextAction NextAction array
     * @param string $returnUrl  Return url to client
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $sessionId, array $nextAction, string $returnUrl)
    {
        parent::__construct(null, $sessionId);

        $this->nextAction = $nextAction;
        $this->returnUrl  = $returnUrl;
    }

    /**
     * @return string
     */
    public function returnUrl(): string
    {
        return $this->returnUrl;
    }
}
