<?php

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;

class ReturnHttpCommandDTOAssembler implements ReturnDTOAssembler
{
    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var SodiumCryptService
     */
    private $cryptService;

    /**
     * ReturnHttpCommandDTOAssembler constructor.
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param CryptService   $cryptService   Crypt Service
     */
    public function __construct(TokenGenerator $tokenGenerator, CryptService $cryptService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->cryptService   = $cryptService;
    }

    /**
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @param Site            $site            Site
     * @return ReturnHttpDTO
     */
    public function assemble(PurchaseProcess $purchaseProcess, Site $site): ReturnHttpDTO
    {
        return new ReturnHttpDTO(
            $purchaseProcess,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );
    }
}
