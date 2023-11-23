<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use ProbillerNG\CascadeServiceClient\Api\CascadeServiceApi;
use ProbillerNG\CascadeServiceClient\ApiException;
use ProbillerNG\CascadeServiceClient\Model\InlineObject;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;

class CascadeClient extends ServiceClient
{
    /**
     * @var CascadeServiceApi
     */
    private $cascadeServiceApi;

    /**
     * CascadeClient constructor.
     * @param CascadeServiceApi $cascadeServiceApi Cascade service api
     */
    public function __construct(CascadeServiceApi $cascadeServiceApi)
    {
        $this->cascadeServiceApi = $cascadeServiceApi;
    }

    /**
     * @param string       $sessionId      Session Id
     * @param InlineObject $cascadePayload Cascade request body parameters
     * @return \ProbillerNG\CascadeServiceClient\Model\InlineResponse200|\ProbillerNG\CascadeServiceClient\Model\InlineResponse400
     * @throws ApiException
     */
    public function retrieveCascade(string $sessionId, InlineObject $cascadePayload)
    {
        return $this->cascadeServiceApi->getApiV1Cascades($sessionId, $cascadePayload);
    }
}
