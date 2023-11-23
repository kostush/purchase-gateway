<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudCsService;

class FraudAdviceCsTranslatingService implements FraudCsService
{
    /**
     * @var FraudCsAdapter
     */
    private $fraudServiceCsAdapter;

    /**
     * FraudServiceCsTranslatingService constructor.
     * @param FraudCsAdapter $fraudCsAdapter The adapter
     */
    public function __construct(FraudCsAdapter $fraudCsAdapter)
    {
        $this->fraudServiceCsAdapter = $fraudCsAdapter;
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection Payment Template Collection
     * @param string                    $sessionId                 Session Id
     * @return void
     */
    public function retrieveAdvice(PaymentTemplateCollection $paymentTemplateCollection, string $sessionId): void
    {
        $this->fraudServiceCsAdapter->retrieveAdvice($paymentTemplateCollection, $sessionId);
    }

    /**
     * @param PaymentTemplateCollection $paymentTemplateCollection
     * @param string                    $siteId
     * @param int                       $initalDays
     */
    public function retrieveAdviceFromConfig(
        PaymentTemplateCollection $paymentTemplateCollection,
        string $siteId,
        int $initalDays
    ): void {
        throw new \BadMethodCallException('This method is not applicable for Fraud CS advice service');
    }
}
