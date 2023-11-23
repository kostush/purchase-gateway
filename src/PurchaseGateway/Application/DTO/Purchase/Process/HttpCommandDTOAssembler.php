<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg\ProcessPurchaseHttpDTOFactory as MgpgProcessFactory;

class HttpCommandDTOAssembler implements ProcessPurchaseDTOAssembler
{
    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /** @var SodiumCryptService */
    private $cryptService;

    private $site;

    /**
     * ProcessPurchaseResponse constructor.
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param Site           $site           Site
     * @param CryptService   $cryptService   Crypt Service
     */
    public function __construct(TokenGenerator $tokenGenerator, Site $site, CryptService $cryptService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->site           = $site;
        $this->cryptService   = $cryptService;
    }

    /**
     * @param GenericPurchaseProcess $purchaseProcess PurchaseProcess
     * @return ProcessPurchaseHttpDTO
     */
    public function assemble(GenericPurchaseProcess $purchaseProcess): ProcessPurchaseHttpDTO
    {
        if($purchaseProcess->isMgpgProcess()) {
            return MgpgProcessFactory::create(
                $purchaseProcess,
                $this->tokenGenerator,
                $this->site,
                $this->cryptService
            );
        }

        return ProcessPurchaseHttpDTOFactory::create(
            $purchaseProcess,
            $this->tokenGenerator,
            $this->site,
            $this->cryptService
        );
    }
}
