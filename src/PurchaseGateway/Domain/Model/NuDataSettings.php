<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class NuDataSettings
{
    /** @var string */
    private $clientId;

    /** @var string */
    private $url;

    /** @var bool */
    private $enabled;

    /**
     * NuDataSettings constructor.
     * @param string $clientId NuData Client Id
     * @param string $url      NuData Url
     * @param bool   $enabled  NuData Enabled
     */
    private function __construct(string $clientId, string $url, bool $enabled)
    {
        $this->clientId = $clientId;
        $this->url      = $url;
        $this->enabled  = $enabled;
    }

    /**
     * @param string $clientId NuData Client Id
     * @param string $url      NuData Url
     * @param bool   $enabled  NuData Enabled
     *
     * @return NuDataSettings
     */
    public static function create(string $clientId, string $url, bool $enabled): self
    {
        return new static($clientId, $url, $enabled);
    }

    /**
     * @return string
     */
    public function clientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'clientId' => $this->clientId(),
            'url'      => $this->url(),
            'enabled'  => $this->enabled()
        ];
    }
}
