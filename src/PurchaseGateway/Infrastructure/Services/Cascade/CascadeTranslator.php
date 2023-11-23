<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade;

use ProbillerNG\CascadeServiceClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;

class CascadeTranslator
{
    /**
     * @param Cascade $cascade Cascade
     * @return Cascade
     */
    public function translate(Cascade $cascade): Cascade
    {
        return $cascade;
    }

    /**
     * @param mixed $cascade Cascade
     * @return Cascade
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function translateCascade($cascade): Cascade
    {
        if ($cascade instanceof InlineResponse200) {
            $billerCollection = BillerCollection::buildBillerCollection($cascade->getBillers());
            return Cascade::create($billerCollection);
        }

        $billerCollection = BillerCollection::buildBillerCollection([Cascade::defaultBiller()]);

        return Cascade::create($billerCollection);
    }
}
