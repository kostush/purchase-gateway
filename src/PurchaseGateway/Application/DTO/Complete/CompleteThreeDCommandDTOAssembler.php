<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Complete;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;

class CompleteThreeDCommandDTOAssembler implements CompleteThreeDDTOAssembler
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
     * CompleteThreeDCommandDTOAssembler constructor.
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param CryptService   $cryptService   Crypt Service
     */
    public function __construct(TokenGenerator $tokenGenerator, CryptService $cryptService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->cryptService   = $cryptService;
    }

    /**
     * @param GenericPurchaseProcess $result Authenticate result
     * @param Site|null              $site   Site
     * @return CompleteThreeDHttpDTO
     */
    public function assemble($result, ?Site $site): CompleteThreeDHttpDTO
    {
        return new CompleteThreeDHttpDTO(
            $result,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );
    }
}
