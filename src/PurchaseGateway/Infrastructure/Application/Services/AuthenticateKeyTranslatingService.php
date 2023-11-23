<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Application\Services\AuthenticateKey;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;

class AuthenticateKeyTranslatingService implements AuthenticateKey
{
    /**
     * @var SiteRepositoryReadOnly
     */
    protected $siteRepository;

    /**
     * AuthenticateKeyTranslatingService constructor.
     * @param SiteRepositoryReadOnly $siteRepository Site Repository Read Only
     */
    public function __construct(SiteRepositoryReadOnly $siteRepository)
    {
        $this->siteRepository = $siteRepository;
    }

    /**
     * @param Site   $site      Site
     * @param string $publicKey Public Key
     * @return int|null
     */
    public function getPublicKeyIndex(Site $site, string $publicKey): ?int
    {
        return $site->publicKeyIndex($publicKey);
    }
}
