<?php

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ForceCascadeException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException;

class CascadeService
{
    /**
     * @var CascadeTranslatingService
     */
    private $cascadeTranslatingService;

    /**
     * CascadeService constructor.
     * @param CascadeTranslatingService $cascadeTranslatingService CascadeTranslatingService
     */
    public function __construct(CascadeTranslatingService $cascadeTranslatingService)
    {
        $this->cascadeTranslatingService = $cascadeTranslatingService;
    }

    /**
     * @param string      $sessionId         Session Id
     * @param string      $siteId            Site Id
     * @param string      $businessGroupId   Business Group Id
     * @param string      $country           Country
     * @param string      $paymentType       Payment type
     * @param string|null $paymentMethod     Payment method
     * @param string|null $trafficSource     Traffic source
     * @param string|null $forceCascade      Force cascade
     * @param string|null $initialJoinBiller Biller used at initial join
     * @return Cascade
     * @throws Exception\UnknownBillerNameException
     * @throws InvalidForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveCascade(
        string $sessionId,
        string $siteId,
        string $businessGroupId,
        string $country,
        string $paymentType,
        ?string $paymentMethod,
        ?string $trafficSource,
        ?string $forceCascade,
        ?string $initialJoinBiller
    ): Cascade {
        if (!empty($forceCascade)) {
            return $this->retrieveForcedCascade($forceCascade);
        }

        if (!empty($initialJoinBiller)) {
            return $this->retrieveCascadeByPaymentTemplateBiller($initialJoinBiller);
        }

        return $this->cascadeTranslatingService->retrieveCascadeForInitPurchase(
            $sessionId,
            $siteId,
            $businessGroupId,
            $country,
            $paymentType,
            $paymentMethod,
            $trafficSource
        );
    }

    /**
     * @param string $forceBiller Force cascade flag
     * @return Cascade
     * @throws \Exception
     * @throws InvalidForceCascadeException
     */
    private function retrieveForcedCascade(string $forceBiller): Cascade
    {
        $biller = BillerFactoryService::createFromForceCascade($forceBiller);

        Log::info(
            'Cascade: ',
            [
                'forceCascade' => true,
                'biller'       => $biller->name()
            ]
        );

        // The same biller is passed twice for backwards compatibility until cascade service will be up an running
        return Cascade::create(BillerCollection::buildBillerCollection([$biller, $biller]));
    }

    /**
     * @param string $paymentTemplateBiller Biller used when the initial join was made
     * @return Cascade
     * @throws Exception\UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function retrieveCascadeByPaymentTemplateBiller(string $paymentTemplateBiller): Cascade
    {
        $biller = BillerFactoryService::create($paymentTemplateBiller);

        Log::info(
            'Cascade: ',
            [
                'initialJoinBillerUsed' => true,
                'biller'                => $biller->name()
            ]
        );

        return Cascade::create(BillerCollection::buildBillerCollection([$biller, $biller]));
    }
}
