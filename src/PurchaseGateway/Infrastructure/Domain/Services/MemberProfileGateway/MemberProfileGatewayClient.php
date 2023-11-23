<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use Illuminate\Http\Response;
use ProbillerNG\MemberProfileGatewayClient\Api\MemberProfileApi;
use ProbillerNG\MemberProfileGatewayClient\Model\InlineResponse200;
use ProbillerNG\MemberProfileGatewayClient\Model\RetrieveMemberProfilePayload;
use ProbillerNG\MemberProfileGatewayClient\ApiException;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\{
    MemberProfileNotFoundException,
    MemberProfileGatewayErrorException
};

class MemberProfileGatewayClient extends ServiceClient
{
    /**
     * @var MemberProfileApi
     */
    private $memberProfileApi;

    /**
     * MemberProfileGatewayClient constructor.
     * @param MemberProfileApi $memberProfileApi MemberProfileApi
     */
    public function __construct(MemberProfileApi $memberProfileApi)
    {
        $this->memberProfileApi = $memberProfileApi;
    }

    /**
     * @param string                       $memberId Member Id
     * @param string                       $xApiKey  xApi key
     * @param RetrieveMemberProfilePayload $payload  Payload
     * @return InlineResponse200|\ProbillerNG\MemberProfileGatewayClient\Model\InlineResponse400
     * @throws MemberProfileGatewayErrorException
     * @throws MemberProfileNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveMemberProfile(string $memberId, string $xApiKey, RetrieveMemberProfilePayload $payload)
    {
        try {
            return $this->memberProfileApi->retrieveMemberProfile($memberId, $xApiKey, $payload);
        } catch (ApiException $exception) {
            switch ($exception->getCode()) {
                case Response::HTTP_NOT_FOUND:
                    throw new MemberProfileNotFoundException($memberId);
                default:
                    throw new MemberProfileGatewayErrorException(null, $exception->getMessage(), $exception->getCode());
            }
        }
    }
}
