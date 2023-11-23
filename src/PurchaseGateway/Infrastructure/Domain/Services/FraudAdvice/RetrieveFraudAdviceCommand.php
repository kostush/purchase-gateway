<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

class RetrieveFraudAdviceCommand extends ExternalCommand
{
    /** @var FraudAdvice */
    private $adapter;

    /** @var SiteId */
    private $siteId;

    /** @var array */
    private $params;

    /** @var string */
    private $for;

    /** @var SessionId */
    private $sessionId;

    /**
     * RetrieveFraudAdviceCommand constructor.
     * @param FraudAdviceAdapter $adapter   Fraud Advice Adapter
     * @param SiteId             $siteId    SiteId
     * @param array              $params    Params
     * @param string             $for       For
     * @param SessionId|null     $sessionId Session Id
     */
    public function __construct(
        FraudAdviceAdapter $adapter,
        SiteId $siteId,
        array $params,
        string $for,
        SessionId $sessionId = null
    ) {
        $this->adapter = $adapter;

        $this->siteId    = $siteId;
        $this->params    = $params;
        $this->for       = $for;
        $this->sessionId = $sessionId;
    }

    /**
     * @return FraudAdvice
     * @throws Exceptions\FraudAdviceApiException
     * @throws Exceptions\FraudAdviceTranslationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     */
    protected function run(): FraudAdvice
    {
        return $this->adapter->retrieveAdvice(
            $this->siteId,
            $this->params,
            $this->for,
            $this->sessionId
        );
    }

    /**
     * @return FraudAdvice
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function getFallback(): FraudAdvice
    {
        Log::info('FraudAdvice service error. Returning default fraudAdvice.');

        return FraudAdvice::create();
    }
}
