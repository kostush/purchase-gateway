<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\ThreeDAuthenticateUrl;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class AuthenticateThreeD extends NextAction
{
    public const TYPE = 'authenticate3D';

    /**
     * @var ThreeDAuthenticateUrl
     */
    private $threeDAuthenticateUrl;

    /**
     * @var int|null
     */
    private $threeDVersion;

    /**
     * @var string|null
     */
    private $threeDStepUpUrl;

    /**
     * @var string|null
     */
    private $threeDStepUpJwt;

    /**
     * Rocketgate biller transaction id.
     *
     * @var string|null
     */
    private $md;

    /**
     * RenderGateway constructor.
     * @param ThreeDAuthenticateUrl $threeDAuthenticateUrl ThreeDAuthenticateUrl object
     * @param Transaction|null      $transaction           Transaction object.
     */
    private function __construct(ThreeDAuthenticateUrl $threeDAuthenticateUrl, ?Transaction $transaction)
    {
        $this->threeDAuthenticateUrl = $threeDAuthenticateUrl;

        if ($transaction && $transaction->threeDStepUpUrl() != null) {
            $this->threeDVersion   = $transaction->threeDVersion();
            $this->threeDStepUpUrl = $transaction->threeDStepUpUrl();
            $this->threeDStepUpJwt = $transaction->threeDStepUpJwt();
            $this->md              = $transaction->md();
        }
    }

    /**
     * @param ThreeDAuthenticateUrl $threeDAuthenticateUrl ThreeDAuthenticateUrl object.
     * @param Transaction|null      $transaction           Transaction object.
     * @return AuthenticateThreeD
     */
    public static function create(ThreeDAuthenticateUrl $threeDAuthenticateUrl, ?Transaction $transaction = null): self
    {
        return new static($threeDAuthenticateUrl, $transaction);
    }

    /**
     * @return ThreeDAuthenticateUrl
     */
    public function threeDAuthenticateUrl(): ThreeDAuthenticateUrl
    {
        return $this->threeDAuthenticateUrl;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $nextAction = [
            'type'    => $this->type(),
            'version' => 1,
            'threeD'  => [
                'authenticateUrl' => (string) $this->threeDAuthenticateUrl()
            ],
        ];

        if ($this->threeDVersion === 2) {
            $nextAction['version']                   = $this->threeDVersion;
            $nextAction['threeD']['authenticateUrl'] = $this->threeDStepUpUrl;
            $nextAction['threeD']['jwt']             = $this->threeDStepUpJwt;
            $nextAction['threeD']['md']              = $this->md;
        }

        return $nextAction;
    }
}
