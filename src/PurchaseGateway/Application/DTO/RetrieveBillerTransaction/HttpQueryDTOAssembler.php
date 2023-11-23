<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction;

use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerDtoException;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;

class HttpQueryDTOAssembler implements BillerTransactionDTOAssembler
{
    private static $billerMapping = [
        RocketgateBiller::BILLER_NAME => RocketgateBillerTransactionQueryHttpDTO::class,
        NetbillingBiller::BILLER_NAME => NetbillingBillerTransactionQueryHttpDTO::class,
        EpochBiller::BILLER_NAME      => EpochBillerTransactionQueryHttpDTO::class
    ];

    /**
     * @param RetrieveTransactionResult $transaction Transaction
     * @return mixed
     * @throws UnknownBillerDtoException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerIdException
     */
    public function assemble(RetrieveTransactionResult $transaction)
    {
        $billerName = $transaction->billerName();

        if (!array_key_exists($billerName, self::$billerMapping)) {
            throw new UnknownBillerDtoException($billerName);
        }

        $dtoClass = self::$billerMapping[$billerName];

        // return biller specific DTO, based on biller name
        return new $dtoClass($transaction);
    }
}
