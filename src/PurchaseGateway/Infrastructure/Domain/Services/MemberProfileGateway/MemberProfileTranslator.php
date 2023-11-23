<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use Illuminate\Http\Response;
use Mockery\Generator\StringManipulation\Pass\Pass;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileGatewayErrorException;
use ProbillerNG\MemberProfileGatewayClient\Model\InlineResponse200;

class MemberProfileTranslator
{
    /**
     * @param string      $memberId       Member Id
     * @param mixed       $result         Result
     * @param string|null $subscriptionId Subscription Id
     * @param string|null $entrySiteId
     * @return MemberInfo
     * @throws MemberProfileGatewayErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     */
    public function translateRetrieveMemberInfo(
        string $memberId,
        $result,
        ?string $subscriptionId,
        ?string $entrySiteId
    ): MemberInfo {
        if (!($result instanceof InlineResponse200)) {
            throw new MemberProfileGatewayErrorException(
                null,
                RetrieveResponse::class,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->createMemberInfo($memberId, $result, $subscriptionId, $entrySiteId);
    }

    /**
     * @param string            $memberId       Member id
     * @param InlineResponse200 $result         Result
     * @param string|null       $subscriptionId Subscription Id
     * @param string|null       $entrySiteId    Entry site ID
     * @return MemberInfo
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \Exception
     */
    protected function createMemberInfo(
        string $memberId,
        InlineResponse200 $result,
        ?string $subscriptionId,
        ?string $entrySiteId
    ): MemberInfo {
        $memberInfo = MemberInfo::create(
            MemberId::createFromString($memberId),
            Email::create($result->getEmail()),
            null,
            $result->getFirstName(),
            $result->getLastName()
        );

        if (is_null($subscriptionId) && is_null($entrySiteId)) {
            return $memberInfo;
        }

        $subscriptions = $result->getSubscriptions();

        if (is_null($subscriptions)) {
            return $memberInfo;
        }

        foreach ($subscriptions as $subscription) {
            if (!is_null($subscriptionId) && $subscription['subscriptionId'] != $subscriptionId) {
                continue;
            }

            if (!empty($entrySiteId) && $subscription['entrySiteId'] != $entrySiteId) {
                continue;
            }

            $memberInfo->setUsername(Username::create($subscription['username']));

            break;

        }

        return $memberInfo;
    }
}
