<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway;

use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveMemberProfileAdapter;

class CircuitBreakerRetrieveMemberProfileAdapter extends CircuitBreaker implements RetrieveMemberProfileAdapter
{
    /**
     * @var RetrieveMemberProfileAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerRetrieveMemberProfileAdapter constructor.
     * @param CommandFactory                      $commandFactory Command factory
     * @param RetrieveMemberProfileServiceAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        RetrieveMemberProfileServiceAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string      $memberId       Member Id
     * @param string      $siteId         Site Id
     * @param string      $publicKey      Public Key
     * @param string      $sessionId      Session Id
     * @param string|null $subscriptionId Subscription Id
     * @param string|null $entrySiteId    Entry Site Id
     * @return MemberInfo
     * @throws \Exception
     */
    public function retrieveMemberProfile(
        string $memberId,
        string $siteId,
        string $publicKey,
        string $sessionId,
        ?string $subscriptionId,
        ?string $entrySiteId
    ): MemberInfo {
        try {
            $command = $this->commandFactory->getCommand(
                RetrieveMemberProfileCommand::class,
                $this->adapter,
                $memberId,
                $siteId,
                $publicKey,
                $sessionId,
                $subscriptionId,
                $entrySiteId
            );

            return $command->execute();

        } catch (RuntimeException $ex) {
            throw $ex->getPrevious() ?? $ex;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
}
