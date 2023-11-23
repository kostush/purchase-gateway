<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceApiException;

class RetrieveFraudRecommendationCommand extends ExternalCommand
{
    /** @var RetrieveFraudRecommendationAdapter */
    private $adapter;

    /** @var string */
    private $siteId;

    /**
     * @var string
     */
    private $businessGroupId;

    /** @var SessionId */
    private $sessionId;

    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $event;

    /**
     * @var array
     */
    private $fraudHeaders;

    /**
     * RetrieveFraudRecommendationCommand constructor.
     *
     * @param RetrieveFraudRecommendationAdapter $adapter       Fraud Advice Adapter
     * @param string                             $businessGroup Business Group
     * @param string                             $siteId        Site Id
     * @param string                             $event         Event
     * @param array                              $data          Data
     * @param string                             $sessionId     Session Id
     * @param array                              $fraudHeaders  Fraud headers
     */
    public function __construct(
        RetrieveFraudRecommendationAdapter $adapter,
        string $businessGroup,
        string $siteId,
        string $event,
        array $data,
        string $sessionId,
        array $fraudHeaders
    ) {
        $this->adapter = $adapter;

        $this->siteId          = $siteId;
        $this->businessGroupId = $businessGroup;
        $this->sessionId       = $sessionId;
        $this->event           = $event;
        $this->data            = $data;
        $this->fraudHeaders    = $fraudHeaders;
    }

    /**
     * @return FraudRecommendationCollection
     * @throws Exception
     * @throws FraudAdviceApiException
     */
    protected function run(): FraudRecommendationCollection
    {
        return $this->adapter->retrieve(
            $this->businessGroupId,
            $this->siteId,
            $this->event,
            $this->data,
            $this->sessionId,
            $this->fraudHeaders
        );
    }

    /**
     * @return FraudRecommendation
     * @throws Exception
     */
    protected function getFallback(): FraudRecommendationCollection
    {
        Log::info('FraudAdvice service error. Returning default fraudAdvice.');
        return new FraudRecommendationCollection([FraudRecommendation::createDefaultAdvice()]);
    }
}
