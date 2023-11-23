<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class ThirdPartyRedirectQueryDTOAssembler implements ThirdPartyRedirectDTOAssembler
{
    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /** @var CryptService */
    private $cryptService;

    /**
     * ThirdPartyRedirectQueryDTOAssembler constructor.
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param CryptService   $cryptService   Crypt Service
     */
    public function __construct(TokenGenerator $tokenGenerator, CryptService $cryptService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->cryptService   = $cryptService;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @param Site|null       $site            Site
     * @return ThirdPartyRedirectHttpDTO
     */
    public function assemble(PurchaseProcess $purchaseProcess, ?Site $site = null): ThirdPartyRedirectHttpDTO
    {
        return new ThirdPartyRedirectHttpDTO($purchaseProcess, $this->tokenGenerator, $this->cryptService, $site);
    }
}
