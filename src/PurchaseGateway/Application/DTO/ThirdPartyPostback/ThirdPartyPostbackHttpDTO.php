<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback;

use ProBillerNG\Base\Application\DTO\HttpDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class ThirdPartyPostbackHttpDTO extends HttpDTO
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $transactionStatus;

    /**
     * PostbackDTO constructor.
     * @param string $sessionId         Session id
     * @param string $transactionStatus Transaction status
     */
    public function __construct(string $sessionId, string $transactionStatus)
    {
        $this->sessionId         = $sessionId;
        $this->transactionStatus = $transactionStatus;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'result'    => $this->transactionStatus === Transaction::STATUS_APPROVED ? 'success' : 'fail'
        ];
    }
}
