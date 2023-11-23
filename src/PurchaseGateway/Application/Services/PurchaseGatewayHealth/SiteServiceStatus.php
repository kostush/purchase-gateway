<?php

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedgerRepository;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly as SiteRepository;

class SiteServiceStatus
{

    const MAX_NUMBER_DAYS_SINCE_LAST_MODIFIED_LEDGER = 4;

    /** @var ProjectionPositionLedgerRepository */
    private $projectionPositionLedgerRepository;

    /** @var SiteRepository */
    private $siteRepository;

    /**
     * SiteServiceStatus constructor.
     * @param ProjectionPositionLedgerRepository $projectionPositionLedgerRepository ProjectionPositionLedger Repository
     * @param SiteRepository                     $siteRepository                     Site Repository
     */
    public function __construct(
        ProjectionPositionLedgerRepository $projectionPositionLedgerRepository,
        SiteRepository $siteRepository
    ) {
        $this->projectionPositionLedgerRepository = $projectionPositionLedgerRepository;
        $this->siteRepository                     = $siteRepository;
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

        return $this->siteRepository->countAll() > 0 ? true : false;
    }
}
