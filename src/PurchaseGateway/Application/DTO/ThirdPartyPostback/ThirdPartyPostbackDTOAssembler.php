<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyPostback;

interface ThirdPartyPostbackDTOAssembler
{
    /**
     * @param string $sessionId         Session id
     * @param string $transactionStatus Transaction status
     * @return ThirdPartyPostbackHttpDTO
     */
    public function assemble(string $sessionId, string $transactionStatus): ThirdPartyPostbackHttpDTO;
}
