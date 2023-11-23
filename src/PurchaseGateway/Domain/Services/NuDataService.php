<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataScoreRequestInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;

interface NuDataService
{
    /**
     * @param string $businessGroupId Business Group Id
     *
     * @return NuDataSettings
     */
    public function retrieveSettings(string $businessGroupId): NuDataSettings;

    /**
     * @param NuDataScoreRequestInfo $nuDataScoreRequestInfo NuData Score Request Info
     * @return string
     */
    public function retrieveScore(NuDataScoreRequestInfo $nuDataScoreRequestInfo): string;
}
