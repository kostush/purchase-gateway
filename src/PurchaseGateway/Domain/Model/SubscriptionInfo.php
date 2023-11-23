<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class SubscriptionInfo
{
    /**
     * @var SubscriptionId
     */
    private $subscriptionId;

    /**
     * @var string
     */
    private $username;

    /**
     * SubscriptionInfo constructor.
     * @param SubscriptionId $subscriptionId subscriptionId
     * @param string         $username       user name
     */
    private function __construct(SubscriptionId $subscriptionId, string $username)
    {
        $this->subscriptionId = $subscriptionId;
        $this->username       = $username;
    }

    /**
     * @param SubscriptionId $subscriptionId subscriptionId
     * @param string         $username       user name
     * @return SubscriptionInfo
     */
    public static function create(SubscriptionId $subscriptionId, string $username): self
    {
        return new static($subscriptionId, $username);
    }

    /**
     * @return SubscriptionId
     */
    public function subscriptionId(): SubscriptionId
    {
        return $this->subscriptionId;
    }

    /**
     * @return string
     */
    public function username(): string
    {
        return $this->username;
    }
}
