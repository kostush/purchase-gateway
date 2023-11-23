<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

abstract class Adapter implements AdapterInterface
{
    /**
     * @param array $params The params
     * @return mixed
     */
    abstract public function get(array $params);
}
