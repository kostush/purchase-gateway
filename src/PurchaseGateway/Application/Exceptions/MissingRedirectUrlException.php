<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\ReturnsNextAction;

class MissingRedirectUrlException extends ValidationException implements ReturnsNextAction
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::MISSING_REDIRECT_URL;

    /**
     * MissingRedirectUrlException constructor.
     * @param array $nextAction NextAction array
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(array $nextAction)
    {
        parent::__construct();

        $this->nextAction = $nextAction;
    }
}
