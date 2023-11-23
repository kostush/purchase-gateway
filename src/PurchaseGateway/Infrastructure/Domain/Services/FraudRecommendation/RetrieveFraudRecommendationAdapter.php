<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice\Exceptions\FraudAdviceApiException;

class RetrieveFraudRecommendationAdapter implements FraudRecommendationAdapter
{

    /**
     * @var FraudRecommendationClient
     */
    private $client;

    /**
     * @var FraudRecommendationTranslator
     */
    private $translator;

    /**
     * FraudRecommendationAdapter constructor.
     * @param FraudRecommendationClient     $client     Fraud Client
     * @param FraudRecommendationTranslator $translator Translator
     */
    public function __construct(FraudRecommendationClient $client, FraudRecommendationTranslator $translator)
    {
        $this->client     = $client;
        $this->translator = $translator;
    }

    /**
     * @param string $businessGroupId Business Group Id
     * @param string $siteId          Site Id
     * @param string $event           Event
     * @param array  $data            Data array
     * @param string $sessionId       Session Id
     * @param array  $fraudHeaders    Fraud headers
     *
     * @return FraudRecommendationCollection
     * @throws FraudAdviceApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieve(
        string $businessGroupId,
        string $siteId,
        string $event,
        array $data,
        string $sessionId,
        array $fraudHeaders
    ): FraudRecommendationCollection {
        try {
            $response = $this->client->retrieve($businessGroupId, $siteId, $event, $data, $sessionId, $fraudHeaders);
            return $this->translator->translate($response);

        } catch (Exception $e) {
            throw new FraudAdviceApiException($e->getMessage());
        }
    }
}
