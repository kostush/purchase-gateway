<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProbillerNG\FraudServiceClient\Api\FraudServiceApi;
use ProbillerNG\FraudServiceClient\ApiException;
use ProbillerNG\FraudServiceClient\Model\Error;
use ProbillerNG\FraudServiceClient\Model\FraudAdvicePayload;
use ProbillerNG\FraudServiceClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;

class FraudAdviceClient extends ServiceClient
{
    /**
     * @var FraudServiceApi
     */
    protected $fraudServiceApi;

    /**
     * FraudAdviceClient constructor.
     * @param FraudServiceApi $fraudServiceApi Fraud Service api
     */
    public function __construct(FraudServiceApi $fraudServiceApi)
    {
        $this->fraudServiceApi = $fraudServiceApi;
    }

    /**
     * @param SiteId             $siteId  SiteId
     * @param FraudAdvicePayload $payload Payload
     *
     * @return InlineResponse200|Error
     *
     * @throws ApiException
     */
    public function retrieveAdvice(SiteId $siteId, $payload)
    {
        return $this->fraudServiceApi->getFraudAdviceForSite($siteId, $payload->getSessionId(), $payload);
    }
}
