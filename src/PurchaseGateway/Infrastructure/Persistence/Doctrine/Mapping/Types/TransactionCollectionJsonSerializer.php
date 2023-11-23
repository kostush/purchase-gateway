<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Mapping\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;

class TransactionCollectionJsonSerializer extends JsonType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'transactionCollection';
    }

    /**
     * @param mixed            $value    object
     * @param AbstractPlatform $platform abstract platform
     * @return mixed|null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $transactions = [];

        /** @var Transaction $transaction */
        foreach ($value->toArray() as $transaction) {
            $transactions[] = [
                'transactionId' => (string) $transaction->transactionId(),
                'state'         => $transaction->state(),
                'billerName'    => $transaction->billerName(),
                'newCCUsed'     => $transaction->newCCUsed() ?? null
            ];
        }

        return json_encode($transactions);
    }

    /**
     * @param mixed            $value    json
     * @param AbstractPlatform $platform abstract platform
     * @return mixed|null
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $dbResult = json_decode($value, true);

        $transactionCollection = new TransactionCollection();

        foreach ($dbResult as $row) {
            if (!empty($row['transactionId'])) {
                $transaction = Transaction::create(
                    TransactionId::createFromString($row['transactionId']),
                    $row['state'],
                    $row['billerName'] ?? RocketgateBiller::BILLER_NAME,
                    $row['newCCUsed'] ?? true
                );
                $transactionCollection->add($transaction);
            }
        }

        return $transactionCollection;
    }
}
