<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\Projection\Domain\ProjectedItem;

class BusinessGroupSite implements ProjectedItem
{
    /** @var int */
    private $id;

    /** @var string */
    private $siteId;

    /** @var string */
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

    /** @var array|null */
    private $serviceCollection;

    /** @var bool */
    private $isNsfSupported;

    /** @var bool */
    private $isStickyGateway;

    /**
     * BusinessGroupSite constructor.
     * @param string      $siteId
     * @param string      $businessGroupId
     * @param string|null $url
     * @param string      $name
     * @param string|null $phoneNumber
     * @param string|null $skypeNumber
     * @param string|null $supportLink
     * @param string|null $mailSupportLink
     * @param string|null $messageSupportLink
     * @param string|null $cancellationLink
     * @param string|null $postbackUrl
     * @param array|null  $serviceCollection
     * @param bool        $isStickyGateway
     * @param bool|null   $isNsfSupported
     */
    private function __construct(
        string $siteId,
        string $businessGroupId,
        ?string $url,
        string $name,
        ?string $phoneNumber,
        ?string $skypeNumber,
        ?string $supportLink,
        ?string $mailSupportLink,
        ?string $messageSupportLink,
        ?string $cancellationLink,
        ?string $postbackUrl,
        ?array $serviceCollection,
        ?bool $isNsfSupported,
        bool $isStickyGateway
    ) {
        $this->siteId             = $siteId;
        $this->businessGroupId    = $businessGroupId;
        $this->url                = $url;
        $this->name               = $name;
        $this->phoneNumber        = $phoneNumber;
        $this->skypeNumber        = $skypeNumber;
        $this->supportLink        = $supportLink;
        $this->mailSupportLink    = $mailSupportLink;
        $this->messageSupportLink = $messageSupportLink;
        $this->cancellationLink   = $cancellationLink;
        $this->postbackUrl        = $postbackUrl;
        $this->serviceCollection  = $serviceCollection;
        $this->isNsfSupported     = (bool) $isNsfSupported;
        $this->isStickyGateway    = (bool) $isStickyGateway;
    }

    /**
     * @param string      $siteId
     * @param string      $businessGroupId
     * @param string|null $url
     * @param string      $name
     * @param string|null $phoneNumber
     * @param string|null $skypeNumber
     * @param string|null $supportLink
     * @param string|null $mailSupportLink
     * @param string|null $messageSupportLink
     * @param string|null $cancellationLink
     * @param string|null $postbackUrl
     * @param array|null  $serviceCollection
     * @param bool|null   $isNsfSupported
     * @param bool        $isStickyGateway Sticky MID
     * @return BusinessGroupSite
     */
    public static function create(
        string $siteId,
        string $businessGroupId,
        ?string $url,
        string $name,
        ?string $phoneNumber,
        ?string $skypeNumber,
        ?string $supportLink,
        ?string $mailSupportLink,
        ?string $messageSupportLink,
        ?string $cancellationLink,
        ?string $postbackUrl,
        ?array $serviceCollection,
        ?bool $isNsfSupported,
        bool $isStickyGateway
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
            $isNsfSupported,
            $isStickyGateway
        );
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->siteId;
    }

    /**
     * @return string
     */
    public function businessGroupId(): string
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
    public function cancellationLink(): string
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
     * @return array|null
     */
    public function serviceCollection(): ?array
    {
        return $this->serviceCollection;
    }

    /**
     * @return bool
     */
    public function isNsfSupported(): bool
    {
        return (bool) $this->isNsfSupported;
    }

    /**
     * @return bool
     */
    public function isStickyGateway(): bool
    {
        return (bool) $this->isStickyGateway;
    }

    /**
     * @return string
     */
    public function className(): string
    {
        return __CLASS__;
    }

    /**
     * @return BusinessGroupSite
     */
    public function representation(): BusinessGroupSite
    {
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'siteId'             => $this->id(),
            'businessGroupId'    => $this->businessGroupId(),
            'url'                => $this->url(),
            'name'               => $this->name(),
            'phoneNumber'        => $this->phoneNumber(),
            'skypeNumber'        => $this->skypeNumber(),
            'supportLink'        => $this->supportLink(),
            'mailSupportLink'    => $this->mailSupportLink(),
            'messageSupportLink' => $this->messageSupportLink(),
            'cancellationLink'   => $this->cancellationLink(),
            'postbackUrl'        => $this->postbackUrl(),
            'serviceCollection'  => $this->serviceCollection(),
            'isNsfSupported'     => $this->isNsfSupported(),
            'isStickyGateway'    => $this->isStickyGateway()
        ];
    }
}
