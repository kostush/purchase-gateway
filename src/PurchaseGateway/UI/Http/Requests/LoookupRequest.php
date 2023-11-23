<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class LoookupRequest extends Request
{
    /**
     * Validation Rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return Site
     */
    public function site(): Site
    {
        return $this->get('site');
    }

    /**
     * @return string
     */
    public function ccNumber(): string
    {
        return (string) (
            $this->json('payment.cardInformation.ccNumber') ?? $this->json('payment.ccNumber', '')
        );
    }

    /**
     * @return string
     */
    public function cvv(): string
    {
        return (string) (
            $this->json('payment.cardInformation.cvv') ?? $this->json('payment.cvv', '')
        );
    }

    /**
     * @return string
     */
    public function cardExpirationMonth(): string
    {
        return (string) (
            $this->json('payment.cardInformation.cardExpirationMonth') ?? $this->json('payment.cardExpirationMonth', '')
        );
    }

    /**
     * @return string
     */
    public function cardExpirationYear(): string
    {
        return (string) (
            $this->json('payment.cardInformation.cardExpirationYear') ?? $this->json('payment.cardExpirationYear', '')
        );
    }

    /**
     * @return array
     */
    public function payment(): array
    {
        return $this->json('payment', []);
    }

    /**
     * @return string
     */
    public function deviceFingerprintingId(): string
    {
        return (string) $this->get('deviceFingerprintingId', '');
    }
}
