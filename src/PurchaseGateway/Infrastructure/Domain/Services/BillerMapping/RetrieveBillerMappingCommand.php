<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;

class RetrieveBillerMappingCommand extends ExternalCommand
{
    /** @var BillerMappingAdapter */
    private $adapter;

    /** @var string */
    private $billerName;

    /** @var string */
    private $businessGroupId;

    /** @var string */
    private $siteId;

    /** @var string */
    private $currencyCode;

    /** @var string */
    private $sessionId;

    /**
     * RetrieveBillerMappingCommand constructor.
     * @param BillerMappingAdapter $adapter         BillerMappingAdapter
     * @param string               $billerName      Biller Name
     * @param string               $businessGroupId Business Group
     * @param string               $siteId          Site Id
     * @param string               $currencyCode    Currency Code
     * @param string               $sessionId       Session Id
     */
    public function __construct(
        BillerMappingAdapter $adapter,
        string $billerName,
        string $businessGroupId,
        string $siteId,
        string $currencyCode,
        string $sessionId
    ) {
        $this->adapter = $adapter;

        $this->billerName      = $billerName;
        $this->businessGroupId = $businessGroupId;
        $this->siteId          = $siteId;
        $this->currencyCode    = $currencyCode;
        $this->sessionId       = $sessionId;
    }

    /**
     * @return BillerMapping
     * @throws Exceptions\BillerMappingApiException
     * @throws Exceptions\BillerMappingErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException
     */
    protected function run(): BillerMapping
    {
        return $this->adapter->retrieveBillerMapping(
            $this->billerName,
            $this->businessGroupId,
            $this->siteId,
            $this->currencyCode,
            $this->sessionId
        );
    }
}
