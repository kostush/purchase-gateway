<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

interface AdapterInterface
{
    /**
     * @param array $params The params
     * @return mixed
     */
     public function get(array $params);
}
