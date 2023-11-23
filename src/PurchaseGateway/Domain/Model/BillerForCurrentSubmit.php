<?php

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;

class BillerForCurrentSubmit
{
    /**
     * @var Biller
     */
    private $biller;

    /**
     * BillerForCurrentSubmit constructor.
     * @param Cascade              $cascade              Cascade
     * @param PaymentTemplate|null $paymentTemplateInUse Payment template in use
     * @throws Exception\InvalidNextBillerException
     * @throws \Exception
     */
    private function __construct(
        Cascade $cascade,
        ?PaymentTemplate $paymentTemplateInUse
    ) {
        $this->biller = $this->initBiller(
            $cascade,
            $paymentTemplateInUse
        );
    }

    /**
     * @param Cascade              $cascade              Cascade
     * @param PaymentTemplate|null $paymentTemplateInUse Payment template in use
     * @return BillerForCurrentSubmit
     * @throws Exception\InvalidNextBillerException
     * @throws \Exception
     */
    public static function create(
        Cascade $cascade,
        ?PaymentTemplate $paymentTemplateInUse
    ): self {
        return new static(
            $cascade,
            $paymentTemplateInUse
        );
    }

    /**
     * @return Biller
     */
    public function biller(): Biller
    {
        return $this->biller;
    }
    /**
     * @param Cascade              $cascade              Cascade
     * @param PaymentTemplate|null $paymentTemplateInUse Payment template in use
     * @return Biller
     * @throws Exception\InvalidNextBillerException
     * @throws \Exception
     */
    private function initBiller(
        Cascade $cascade,
        ?PaymentTemplate $paymentTemplateInUse
    ): Biller {

        if ($paymentTemplateInUse instanceof PaymentTemplate) {
            return BillerFactoryService::create($paymentTemplateInUse->billerName());
        }

        return $cascade->nextBiller();
    }
}
