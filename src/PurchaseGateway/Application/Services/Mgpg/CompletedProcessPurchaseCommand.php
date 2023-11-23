<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Mgpg;

class CompletedProcessPurchaseCommand extends ProcessPurchaseCommand
{
    /**
     * ProcessPurchaseCommand constructor.
     * @param string $ngSessionId
     * @param string $correlationId
     * @param int    $publicKeyId
     * @param string $mgpgSessionId
     * @param string $postbackUrl
     * @param string $returnUrl
     */
    public function __construct(
        string $ngSessionId,
        string $correlationId,
        int $publicKeyId,
        // Some fields don't need to be set if we receive a Process Purchase from a completed purchase(return/postback)
        string $mgpgSessionId = '',
        string $postbackUrl = '',
        string $returnUrl = ''
    ) {
        parent::__construct(
            $ngSessionId,
            $correlationId,
            $mgpgSessionId,
            $publicKeyId,
            $postbackUrl,
            $returnUrl
        );
    }
}
