<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

/**
 * Class Service
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 */
class Service
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var array
     */
    private $options;

    /**
     * Service constructor.
     * @param string $name    Name of service
     * @param bool   $enabled Enabled or disabled
     * @param array  $options Options
     */
    private function __construct(string $name, bool $enabled, array $options = [])
    {
        $this->name    = $name;
        $this->enabled = $enabled;
        $this->options = $options;
    }

    /**
     * @param string $name    Name of service
     * @param bool   $enabled Enabled or disabled
     * @param array  $options Options
     * @return static
     */
    public static function create(string $name, bool $enabled, array $options = []): self
    {
        return new static($name, $enabled, $options);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
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
    public function options(): ?array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $service = [
            'name'    => $this->name(),
            'enabled' => $this->enabled()
        ];

        if (!empty($this->options())) {
            $service['options'] = $this->options();
        }

        return $service;
    }
}
