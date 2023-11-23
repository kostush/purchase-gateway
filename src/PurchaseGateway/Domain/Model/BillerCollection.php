<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;

class BillerCollection extends Collection
{
    /**
     * {@inheritdoc}
     * @param mixed $object object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof Biller;
    }

    /**
     * @param array $billers Array of billers
     * @return BillerCollection
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public static function buildBillerCollection(array $billers): self
    {
        $billerCollection = new self();
        foreach ($billers as $biller) {
            if ($biller instanceof Biller) {
                $billerCollection->add($biller);

                continue;
            }

            $billerCollection->add(BillerFactoryService::create($biller));
        }

        return $billerCollection;
    }
}
