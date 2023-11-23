<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\ReturnsNextAction;

class MissingParesAndMdException extends ValidationException implements ReturnsNextAction
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::MISSING_PARES_AND_MD;

    /** @var string */
    protected $returnUrl;

    /**
     * @param array  $nextAction NextAction array
     * @param string $returnUrl  Return url to client
     * @throws Exception
     */
    public function __construct(array $nextAction, string $returnUrl)
    {
        parent::__construct();

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
