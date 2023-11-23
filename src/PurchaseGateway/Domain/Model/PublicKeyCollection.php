<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Base\Domain\Collection;

class PublicKeyCollection extends Collection
{
    /**
     * @return array
     */
    public function toArray(): array
    {
        $publicKeyArr = $this->map(
            /** @return PublicKey $publicKey */
            function ($publicKey) {
                return [
                    'key'       => (string) $publicKey->key(),
                    'createdAt' => $publicKey->createdAt()
                ];
            }
        )->getValues();

        return $publicKeyArr;
    }

    /**
     * @param mixed $object Object
     * @return bool
     */
    protected function isValidObject($object): bool
    {
        return $object instanceof PublicKey;
    }
}
