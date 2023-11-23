<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;

class RedirectToUrl extends NextAction
{
    /**
     * @var string
     */
    public const TYPE = 'redirectToUrl';

    /**
     * @var ThirdParty
     */
    private $thirdParty;

    /**
     * RedirectToUrl constructor.
     * @param ThirdParty $thirdParty Third Party.
     */
    private function __construct(ThirdParty $thirdParty)
    {
        $this->thirdParty = $thirdParty;
    }

    /**
     * @param ThirdParty $thirdParty Third Party.
     * @return RedirectToUrl
     */
    public static function create(ThirdParty $thirdParty): self
    {
        return new static($thirdParty);
    }

    /**
     * @return ThirdParty
     */
    public function thirdParty(): ThirdParty
    {
        return $this->thirdParty;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'       => $this->type(),
            'thirdParty' => [
                'url' => $this->thirdParty()->url()
            ]
        ];
    }
}
