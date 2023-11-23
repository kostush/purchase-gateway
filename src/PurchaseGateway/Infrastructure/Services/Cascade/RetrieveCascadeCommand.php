<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;

class RetrieveCascadeCommand extends ExternalCommand
{
    /**
     * @var CascadeAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $siteId;

    /**
     * @var string
     */
    private $businessGroupId;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var string|null
     */
    private $paymentMethod;

    /**
     * @var string|null
     */
    private $trafficSource;

    /**
     * RetrieveCascadeCommand constructor.
     * @param CascadeAdapter $adapter         CascadeAdapter
     * @param string         $sessionId       Session Id
     * @param string         $siteId          Site Id
     * @param string         $businessGroupId Business Group Id
     * @param string         $country         Country
     * @param string         $paymentType     Payment type
     * @param string|null    $paymentMethod   Payment method
     * @param string|null    $trafficSource   Traffic source
     */
    public function __construct(
        CascadeAdapter $adapter,
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource
    ) {
        $this->adapter         = $adapter;
        $this->sessionId       = $sessionId;
        $this->siteId          = $siteId;
        $this->businessGroupId = $businessGroupId;
        $this->country         = $country;
        $this->paymentType     = $paymentType;
        $this->paymentMethod   = $paymentMethod;
        $this->trafficSource   = $trafficSource;
    }

    /**
     * @return Cascade
     */
    protected function run(): Cascade
    {
        return $this->adapter->get(
            $this->sessionId,
            $this->siteId,
            $this->businessGroupId,
            $this->country,
            $this->paymentType,
            $this->paymentMethod,
            $this->trafficSource
        );
    }

    /**
     * @return Cascade
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    protected function getFallback(): Cascade
    {
        Log::error(
            'Cascade service error. Returning default Cascade.',
            [
                'billers' => BillerCollection::buildBillerCollection([new RocketgateBiller()])->toArray()
            ]
        );

        return Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]));
    }
}
