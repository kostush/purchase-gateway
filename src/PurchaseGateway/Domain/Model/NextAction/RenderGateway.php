<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\ThreeD;

class RenderGateway extends NextAction
{
    /** @var string */
    public const TYPE = 'renderGateway';

    /** @var int */
    public const THREE_DS_FIRST_VERSION = 1;

    /** @var ThreeD */
    private $threeD;

    /**
     * RenderGateway constructor.
     * @param ThreeD|null $threeD ThreeD object
     */
    private function __construct(?ThreeD $threeD)
    {
        $this->threeD = $threeD;
    }

    /**
     * @param ThreeD|null $threeD ThreeD object
     * @return RenderGateway
     */
    public static function create(?ThreeD $threeD = null): self
    {
        return new static($threeD);
    }

    /**
     * @return ThreeD|null
     */
    public function threeD(): ?ThreeD
    {
        return $this->threeD;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = ['type' => $this->type()];

        if ($this->threeD() instanceof ThreeD) {
            $result['threeD'] = [
                'force3DSecure' => $this->threeD()->forceThreeDSecure(),
                'detect3DUsage' => $this->threeD()->detectThreeDUsage()
            ];
        }

        return $result;
    }
}
