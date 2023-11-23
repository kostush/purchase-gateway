<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Lookup;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;

class LookupThreeDCommandDTOAssembler implements LookupThreeDDTOAssembler
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
     * LookupThreeDCommandDTOAssembler constructor.
     * @param TokenGenerator $tokenGenerator Token Generator
     * @param CryptService   $cryptService   Crypt Service
     */
    public function __construct(TokenGenerator $tokenGenerator, CryptService $cryptService)
    {
        $this->tokenGenerator = $tokenGenerator;
        $this->cryptService   = $cryptService;
    }

    /**
     * @param GenericPurchaseProcess $result Lookup result
     * @param Site|null              $site   Site
     * @return LookupThreeDHttpDTO
     */
    public function assemble($result, ?Site $site): LookupThreeDHttpDTO
    {
        return new LookupThreeDHttpDTO(
            $result,
            $this->tokenGenerator,
            $site,
            $this->cryptService
        );
    }
}
