<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedgerRepository;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;

class BundleServiceStatus
{

    const MAX_NUMBER_DAYS_SINCE_LAST_MODIFIED_LEDGER = 4;

    /** @var ProjectionPositionLedgerRepository */
    private $projectionPositionLedgerRepository;

    /** @var BundleRepository */
    private $bundleRepository;

    /**
     * @param ProjectionPositionLedgerRepository $projectionPositionLedgerRepository ProjectionPositionLedger Repository
     * @param BundleRepository                   $bundleRepository                   Bundle Repository
     */
    public function __construct(
        ProjectionPositionLedgerRepository $projectionPositionLedgerRepository,
        BundleRepository $bundleRepository
    ) {
        $this->projectionPositionLedgerRepository = $projectionPositionLedgerRepository;
        $this->bundleRepository                   = $bundleRepository;
    }

    /**
     * @param string $name Projection name
     * @return bool
     * @throws \Exception
     */
    public function ledgerStatus(string $name): bool
    {
        $projectionPositionLedger = $this->projectionPositionLedgerRepository->findByName($name);

        if (!$projectionPositionLedger) {
            return false;
        }

        $interval = $projectionPositionLedger->lastModified()->diff(new \DateTimeImmutable());

        if ($interval->d > self::MAX_NUMBER_DAYS_SINCE_LAST_MODIFIED_LEDGER) {
            return false;
        }

        return $this->bundleRepository->count([]) > 0 ? true : false;
    }
}
