<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI\Processed;

use Illuminate\Contracts\Support\Arrayable;

class Base implements Arrayable
{

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
