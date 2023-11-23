<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation;

use CommonServices\FraudServiceClient\Model\AdviceResponseModelResult;
use CommonServices\FraudServiceClient\Model\AdviceResponseModelV3;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\Exception\FraudRecommendationClientException;

class FraudRecommendationTranslator
{
    /**
     * @param array $response Response array
     * @return FraudRecommendationCollection
     * @throws FraudRecommendationClientException
     * @throws Exception
     */
    public function translate(?array $response): FraudRecommendationCollection
    {
        if (empty($response)) {
            Log::info('FraudRecommendation No response from service, returning default advice');
            return new FraudRecommendationCollection([FraudRecommendation::createDefaultAdvice()]);
        }
        $this->assertResponseIsValid($response);
        $responseCollection = $this->transformResponseIntoCollection($response[0]);

        $collection = new FraudRecommendationCollection();
        foreach ($responseCollection as $response) {
            $collection->add(FraudRecommendation::create($response['code'], $response['severity'], $response['message']));
        }

        return $collection;
    }

    /**
     * @param AdviceResponseModelV3 $adviceResponseModelV3
     * @return array
     */
    public function transformResponseIntoCollection(AdviceResponseModelV3 $adviceResponseModelV3): array
    {
        return array_map([$this,'transformAdviceIntoArray'], $adviceResponseModelV3->getResult());
    }

    /**
     * @param AdviceResponseModelResult $advice
     * @return array
     */
    public function transformAdviceIntoArray(AdviceResponseModelResult $advice): array
    {
        return [
            'code'     => (int) $advice->getCode() ?? 0,
            'message'  => $advice->getMessage() ?? '',
            'severity' => $advice->getSeverity() ?? '',
        ];
    }

    /**
     * @param array $response
     * @throws FraudRecommendationClientException
     * @throws Exception
     */
    private function assertResponseIsValid(?array $response)
    {
        if (empty($response) || !isset($response[0]) || !($response[0] instanceof AdviceResponseModelV3) || $this->isEmptyResult($response[0])) {
            throw new FraudRecommendationClientException();
        }
    }

    /**
     * @param AdviceResponseModelV3 $response
     * @return bool
     * @throws Exception
     */
    private function isEmptyResult(AdviceResponseModelV3 $response): bool
    {
        if (empty($response->getResult())) {
            Log::alert("AdviceResponseModelV3 returned empty result. Missing configuration on Fraud Recommendation service.");
            return true;
        }

        return false;
    }
}
