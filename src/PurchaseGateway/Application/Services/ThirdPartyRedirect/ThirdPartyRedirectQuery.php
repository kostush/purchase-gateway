<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect;

use ProBillerNG\Base\Application\Services\Query;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class ThirdPartyRedirectQuery extends Query
{
    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * ThirdPartyRedirectQuery constructor.
     * @param string $sessionId sessionId
     * @throws \Exception
     */
    public function __construct(string $sessionId)
    {
        $this->initSessionId($sessionId);
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId Session id
     * @return void
     * @throws \Exception
     */
    public function initSessionId(string $sessionId)
    {
        $this->sessionId = SessionId::createFromString($sessionId);
    }
}
