<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use CommonServices\FraudServiceClient\Model\FraudResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

/**
 * @deprecated
 * Class FraudAdviceCsTranslator
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs
 */
class FraudAdviceCsTranslator
{
    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection PaymentTemplateCollection
     * @param mixed                     $fraudServiceResult        Result
     * @return void
     * @throws Exceptions\FraudAdviceCsCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function translate(
        PaymentTemplateCollection $paymentTemplateCollection,
        $fraudServiceResult
    ): void {
        if (!$fraudServiceResult instanceof FraudResponseDto) {
            throw new Exceptions\FraudAdviceCsCodeTypeException(null, FraudResponseDto::class);
        }

        $binCollectionMap = array_column($paymentTemplateCollection->toArray(), 'templateId', 'firstSix');

        foreach ($fraudServiceResult->getSafebin() as $bin => $isSafe) {
            $paymentTemplateCollection->get($binCollectionMap[$bin])->setIsSafe($isSafe);
        }
    }
}
