<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveMemberProfileAdapter;

class RetrieveMemberProfileCommand extends ExternalCommand
{
    /**
     * @var RetrieveMemberProfileServiceAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $memberId;

    /**
     * @var string
     */
    private $siteId;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string|null
     */
    private $subscriptionId;

    /**
     * @var string|null
     */
    private $entrySiteId;


    /**
     * RetrieveMemberProfileCommand constructor.
     * @param RetrieveMemberProfileServiceAdapter $adapter        Adapter
     * @param string                              $memberId       Member Id
     * @param string                              $siteId         Site Id
     * @param string                              $publicKey      Public Key
     * @param string                              $sessionId      Session Id
     * @param string|null                         $subscriptionId Subscription Id
     * @param string|null                         $entrySiteId    Entry site Id
     */
    public function __construct(
        RetrieveMemberProfileServiceAdapter $adapter,
        string $memberId,
        string $siteId,
        string $publicKey,
        string $sessionId,
        ?string $subscriptionId,
        ?string $entrySiteId
    ) {
        $this->adapter        = $adapter;
        $this->memberId       = $memberId;
        $this->siteId         = $siteId;
        $this->sessionId      = $sessionId;
        $this->publicKey      = $publicKey;
        $this->subscriptionId = $subscriptionId;
        $this->entrySiteId    = $entrySiteId;
    }

    /**
     * @return MemberInfo
     * @throws Exceptions\MemberProfileGatewayErrorException
     * @throws Exceptions\MemberProfileGatewayTypeException
     * @throws Exceptions\MemberProfileNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     */
    protected function run(): MemberInfo
    {
        return $this->adapter->retrieveMemberProfile(
            $this->memberId,
            $this->siteId,
            $this->publicKey,
            $this->sessionId,
            $this->subscriptionId,
            $this->entrySiteId
        );
    }

    /**
     * @return  \Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function getFallback(): \Exception
    {
        $exception = $this->getExecutionException();
        Log::logException($exception);

        if (!is_null($exception->getPrevious())) {
            throw $exception->getPrevious();
        }

        throw $exception;
    }
}
