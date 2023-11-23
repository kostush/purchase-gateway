<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Lookup;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class LookupThreeDHttpDTO extends ProcessPurchaseGeneralHttpDTO
{
    /**
     * @var array
     */
    protected $response;

    /**
     * LookupThreeDHttpDTO constructor.
     * @param GenericPurchaseProcess $purchaseProcess PurchaseProcess
     * @param TokenGenerator         $tokenGenerator  Token Generator
     * @param Site                   $site            Site
     * @param CryptService|null      $cryptService    Crypt Service
     */
    public function __construct(
        GenericPurchaseProcess $purchaseProcess,
        TokenGenerator $tokenGenerator,
        Site $site,
        ?CryptService $cryptService
    ) {
        parent::__construct($purchaseProcess, $tokenGenerator, $site, $cryptService);
    }
}
