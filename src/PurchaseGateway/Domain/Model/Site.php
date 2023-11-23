<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Projection\Domain\ProjectedItem;

class Site implements ProjectedItem
{
    /**
     * @var int
     */
    public const DEFAULT_NUMBER_OF_ATTEMPTS = 2;

    /** @var string */
    private $id;

    /** @var SiteId */
    private $siteId;

    /** @var BusinessGroupId */
    private $businessGroupId;

    /** @var string|null */
    private $url;

    /** @var string */
    private $name;

    /** @var string|null */
    private $phoneNumber;

    /** @var string|null */
    private $skypeNumber;

    /** @var string|null */
    private $supportLink;

    /** @var string|null */
    private $mailSupportLink;

    /** @var string|null */
    private $messageSupportLink;

    /** @var string|null */
    private $cancellationLink;

    /** @var string|null */
    private $postbackUrl;

    /** @var ServiceCollection|null */
    private $serviceCollection;

    /** @var string|null */
    private $privateKey;

    /** @var PublicKeyCollection */
    private $publicKeyCollection;

    /** @var string|null */
    private $descriptor;

    /** @var boolean */
    private $isStickyGateway;

    /** @var bool */
    private $isNsfSupported;

    /**
     * @var int
     */
    private $attempts;

    /**
     * Site constructor.
     *
     * @param SiteId                 $siteId
     * @param BusinessGroupId        $businessGroupId
     * @param string|null            $url
     * @param string                 $name
     * @param string|null            $phoneNumber
     * @param string|null            $skypeNumber
     * @param string|null            $supportLink
     * @param string|null            $mailSupportLink
     * @param string|null            $messageSupportLink
     * @param string|null            $cancellationLink
     * @param string|null            $postbackUrl
     * @param ServiceCollection|null $serviceCollection
     * @param string                 $privateKey
     * @param PublicKeyCollection    $publicKeyCollection
     * @param string|null            $descriptor
     * @param bool                   $isStickyGateway
     * @param bool|null              $isNsfSupported
     * @param int                    $attempts
     */
    private function __construct(
        SiteId $siteId,
        BusinessGroupId $businessGroupId,
        ?string $url,
        string $name,
        ?string $phoneNumber,
        ?string $skypeNumber,
        ?string $supportLink,
        ?string $mailSupportLink,
        ?string $messageSupportLink,
        ?string $cancellationLink,
        ?string $postbackUrl,
        ?ServiceCollection $serviceCollection,
        string $privateKey,
        PublicKeyCollection $publicKeyCollection,
        ?string $descriptor,
        bool $isStickyGateway,
        ?bool $isNsfSupported = false,
        int $attempts
    ) {
        $this->siteId              = $siteId;
        $this->businessGroupId     = $businessGroupId;
        $this->url                 = $url;
        $this->name                = $name;
        $this->phoneNumber         = $phoneNumber;
        $this->skypeNumber         = $skypeNumber;
        $this->supportLink         = $supportLink;
        $this->mailSupportLink     = $mailSupportLink;
        $this->messageSupportLink  = $messageSupportLink;
        $this->cancellationLink    = $cancellationLink;
        $this->postbackUrl         = $postbackUrl;
        $this->serviceCollection   = $serviceCollection;
        $this->privateKey          = $privateKey;
        $this->publicKeyCollection = $publicKeyCollection;
        $this->descriptor          = $descriptor;
        $this->isStickyGateway     = $isStickyGateway;
        $this->isNsfSupported      = $isNsfSupported;
        $this->attempts            = $attempts;
    }

    /**
     * @param SiteId                 $siteId
     * @param BusinessGroupId        $businessGroupId
     * @param string|null            $url
     * @param string                 $name
     * @param string|null            $phoneNumber
     * @param string|null            $skypeNumber
     * @param string|null            $supportLink
     * @param string|null            $mailSupportLink
     * @param string|null            $messageSupportLink
     * @param string|null            $cancellationLink
     * @param string|null            $postbackUrl
     * @param ServiceCollection|null $serviceCollection
     * @param string                 $privateKey
     * @param PublicKeyCollection    $publicKeyCollection
     * @param string|null            $descriptor
     * @param bool                   $isStickyGateway
     * @param bool|null              $isNsfSupported
     * @param int                    $attempts
     *
     * @return Site
     */
    public static function create(
        SiteId $siteId,
        BusinessGroupId $businessGroupId,
        ?string $url,
        string $name,
        ?string $phoneNumber,
        ?string $skypeNumber,
        ?string $supportLink,
        ?string $mailSupportLink,
        ?string $messageSupportLink,
        ?string $cancellationLink,
        ?string $postbackUrl,
        ?ServiceCollection $serviceCollection,
        string $privateKey,
        PublicKeyCollection $publicKeyCollection,
        ?string $descriptor,
        bool $isStickyGateway,
        ?bool $isNsfSupported = false,
        int $attempts
    ): self {
        return new static(
            $siteId,
            $businessGroupId,
            $url,
            $name,
            $phoneNumber,
            $skypeNumber,
            $supportLink,
            $mailSupportLink,
            $messageSupportLink,
            $cancellationLink,
            $postbackUrl,
            $serviceCollection,
            $privateKey,
            $publicKeyCollection,
            $descriptor,
            $isStickyGateway,
            $isNsfSupported,
            $attempts
        );
    }

    /**
     * @param string|null         $descriptor          Descriptor
     * @param string              $privateKey          Private key
     * @param PublicKeyCollection $publicKeyCollection Public key collection
     * @return void
     */
    public function updateSiteWithBusinessGroupInfo(
        ?string $descriptor,
        string $privateKey,
        PublicKeyCollection $publicKeyCollection
    ): void {
        $this->descriptor          = $descriptor;
        $this->privateKey          = $privateKey;
        $this->publicKeyCollection = $publicKeyCollection;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return (string) $this->siteId;
    }

    /**
     * @return SiteId
     */
    public function siteId(): SiteId
    {
        return $this->siteId;
    }

    /**
     * @return BusinessGroupId
     */
    public function businessGroupId(): BusinessGroupId
    {
        return $this->businessGroupId;
    }

    /**
     * @return string|null
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return string|null
     */
    public function skypeNumber(): ?string
    {
        return $this->skypeNumber;
    }

    /**
     * @return string|null
     */
    public function supportLink(): ?string
    {
        return $this->supportLink;
    }

    /**
     * @return string|null
     */
    public function mailSupportLink(): ?string
    {
        return $this->mailSupportLink;
    }

    /**
     * @return string|null
     */
    public function messageSupportLink(): ?string
    {
        return $this->messageSupportLink;
    }

    /**
     * @return string|null
     */
    public function cancellationLink(): ?string
    {
        return $this->cancellationLink;
    }

    /**
     * @return string|null
     */
    public function postbackUrl(): ?string
    {
        return $this->postbackUrl;
    }

    /**
     * @return ServiceCollection|null
     */
    public function serviceCollection(): ?ServiceCollection
    {
        return $this->serviceCollection;
    }

    /**
     * @return array
     */
    public function services(): array
    {
        $services = [];
        if (!is_null($this->serviceCollection)) {
            foreach ($this->serviceCollection as $service) {
                $services[$service->name()] = $service;
            }
        }

        return $services;
    }

    /**
     * @return string
     */
    public function privateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * @return PublicKeyCollection|null
     */
    public function publicKeyCollection(): ?PublicKeyCollection
    {
        return $this->publicKeyCollection;
    }

    /**
     * @return array
     */
    public function publicKeys(): array
    {
        $publicKeys = [];
        if (!is_null($this->publicKeyCollection)) {
            foreach ($this->publicKeyCollection->toArray() as $publicKey) {
                $publicKeys[] = $publicKey['key'];
            }
        }

        return $publicKeys;
    }

    /**
     * @param string $sitePublicKey Site Public Key
     * @return int|null
     */
    public function publicKeyIndex(string $sitePublicKey): ?int
    {
        return array_flip($this->publicKeys())[$sitePublicKey] ?? null;
    }

    /**
     * @return string|null
     */
    public function descriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * BG-42324
     * make a configuration flag on the sites table to activate this functionality.
     *
     * @return bool
     */
    public function isStickyGateway(): bool
    {
        return $this->isStickyGateway;
    }

    /**
     * @param string $serviceName Service Name
     * @return bool
     */
    private function isServiceEnabled(string $serviceName): bool
    {
        $services = $this->serviceCollection->toArray();
        $key      = array_search($serviceName, array_column($services, 'name'));
        if ($key !== false) {
            return $services[$key]['enabled'];
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFraudServiceEnabled(): bool
    {
        return $this->isServiceEnabled(ServicesList::FRAUD);
    }

    /**
     * @return bool
     */
    public function isNsfSupported(): bool
    {
        return $this->isNsfSupported;
    }

    /**
     * @return bool
     */
    public function isBinRoutingServiceEnabled(): bool
    {
        return $this->isServiceEnabled(ServicesList::BIN_ROUTING);
    }

    /**
     * @return string
     */
    public function className(): string
    {
        return __CLASS__;
    }

    /**
     * @return Site
     */
    public function representation(): Site
    {
        return $this;
    }

    /**
     * @return int
     */
    public function attempts(): int
    {
        if($this->attempts > 0) {
            return $this->attempts;
        }
        return self::DEFAULT_NUMBER_OF_ATTEMPTS;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'siteId'              => $this->id(),
            'businessGroupId'     => (string) $this->businessGroupId(),
            'url'                 => $this->url(),
            'name'                => $this->name(),
            'phoneNumber'         => $this->phoneNumber(),
            'skypeNumber'         => $this->skypeNumber(),
            'supportLink'         => $this->supportLink(),
            'mailSupportLink'     => $this->mailSupportLink(),
            'messageSupportLink'  => $this->messageSupportLink(),
            'cancellationLink'    => $this->cancellationLink(),
            'postbackUrl'         => $this->postbackUrl(),
            'serviceCollection'   => $this->serviceCollection()->toArray(),
            'privateKey'          => $this->privateKey(),
            'publicKeyCollection' => $this->publicKeyCollection()->toArray(),
            'descriptor'          => $this->descriptor(),
            'isStickyGateway'     => $this->isStickyGateway(),
            'isNsfSupported'      => $this->isNsfSupported(),
            'attempts'            => $this->attempts()
        ];
    }
}
