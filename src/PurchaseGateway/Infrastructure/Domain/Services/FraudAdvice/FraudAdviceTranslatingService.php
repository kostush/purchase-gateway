<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;

class FraudAdviceTranslatingService implements FraudService
{
    /**
     * @var FraudAdapter
     */
    private $fraudAdviceAdapter;

    /**
     * FraudAdviceTranslatingService constructor.
     * @param FraudAdapter $fraudAdviceAdapter Adapter
     */
    public function __construct(FraudAdapter $fraudAdviceAdapter)
    {
        $this->fraudAdviceAdapter = $fraudAdviceAdapter;
    }

    /**
     * @param SiteId         $siteId    Site id
     * @param array          $params    Params
     * @param string         $for       For which step
     * @param SessionId|null $sessionId Session id
     *
     * @return FraudAdvice
     */
    public function retrieveAdvice(SiteId $siteId, array $params, string $for, SessionId $sessionId = null): FraudAdvice
    {
        return $this->fraudAdviceAdapter->retrieveAdvice($siteId, $params, $for, $sessionId);
    }
}
