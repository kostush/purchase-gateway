<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class ThirdParty
{
    /**
     * @var string
     */
    private $url;

    /**
     * ThirdParty constructor.
     * @param string $url Url for redirect to third party biller.
     */
    private function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @param string $url Url for redirect to third party biller.
     * @return ThirdParty
     */
    public static function create(string $url): self
    {
        return new static($url);
    }

    /**
     * @return string
     */
    public function url(): string
    {
        return $this->url;
    }
}
