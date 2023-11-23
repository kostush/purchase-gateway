<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

abstract class BasePaymentTemplateAdapter
{
    /**
     * @var PaymentTemplateClient
     */
    protected $client;

    /**
     * @var PaymentTemplateTranslator
     */
    protected $translator;

    /**
     * BasePaymentTemplateAdapter constructor.
     * @param PaymentTemplateClient     $client     Client
     * @param PaymentTemplateTranslator $translator Translator
     */
    public function __construct(
        PaymentTemplateClient $client,
        PaymentTemplateTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }
}
