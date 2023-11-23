<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class ProcessPurchaseRequest extends Request
{
    /**
     * Validation Rules.
     *
     * @var array
     */
    protected $rules = [
        'payment.paymentTemplateInformation'                   => 'array',
        'payment.paymentTemplateInformation.paymentTemplateId' => 'required_with:payment.paymentTemplateInformation|uuid'
    ];

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    public function overrides(): ?array
    {
        return $this->get('overrides', null);
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
    public function memberEmail(): string
    {
        return (string) $this->json('member.email', '');
    }

    /**
     * @return string
     */
    public function memberUsername(): string
    {
        return (string) $this->json('member.username', '');
    }

    /**
     * @return string
     */
    public function memberPassword(): string
    {
        return (string) $this->json('member.password', '');
    }

    /**
     * @return string
     */
    public function memberFirstName(): string
    {
        return (string) $this->json('member.firstName', '');
    }

    /**
     * @return string
     */
    public function memberLastName(): string
    {
        return (string) $this->json('member.lastName', '');
    }

    /**
     * @return string
     */
    public function memberCountryCode(): string
    {
        return (string) $this->json('member.countryCode', '');
    }

    /**
     * @return string
     */
    public function memberZipCode(): string
    {
        return (string) $this->json('member.zipCode', '');
    }

    /**
     * @return string
     */
    public function memberAddress1(): string
    {
        return (string) $this->json('member.address1', '');
    }

    /**
     * @return string
     */
    public function memberAddress2(): string
    {
        return (string) $this->json('member.address2', '');
    }

    /**
     * @return string
     */
    public function memberCity(): string
    {
        return (string) $this->json('member.city', '');
    }

    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        return trim(
            sprintf(
                '%s %s',
                $this->json('member.address1', ''),
                $this->json('member.address2', '')
            )
        );
    }

    /**
     * @return string
     */
    public function memberState(): string
    {
        return (string) $this->json('member.state', '');
    }

    /**
     * @return string
     */
    public function memberPhone(): string
    {
        return (string) $this->json('member.phone');
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
     * @return string
     */
    public function lastFour(): string
    {
        return (string) $this->json('payment.paymentTemplateInformation.lastFour', '');
    }

    /**
     * @return string
     */
    public function accountNumberLast4(): string
    {
        return (string) $this->json('payment.paymentTemplateInformation.accountNumberLast4', '');
    }

    /**
     * @return string
     */
    public function paymentTemplateId(): string
    {
        return (string) $this->json('payment.paymentTemplateInformation.paymentTemplateId', '');
    }

    /**
     * @return string
     */
    public function paymentMethod(): string
    {
        return (string) $this->json('payment.method', '');
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return (string) $this->json('payment.type', '');
    }

    /**
     * @return null|array
     */
    public function selectedCrossSells(): array
    {
        return $this->json('selectedCrossSells', []);
    }

    /**
     * @return array
     */
    public function member(): array
    {
        return $this->json('member', []);
    }

    /**
     * @return array
     */
    public function payment(): array
    {
        return $this->json('payment', []);
    }

    /**
     * @return string|null
     */
    public function ndWidgetData(): ?string
    {
        return (string) $this->get('ndWidgetData', null);
    }

    /**
     * @return string|null
     */
    public function routingNumber(): ?string
    {
        return (string) (
            $this->json('payment.checkInformation.routingNumber', null)
        );
    }

    /**
     * @return string|null
     */
    public function accountNumber(): ?string
    {
        return (string) (
            $this->json('payment.checkInformation.accountNumber', null)
        );
    }

    /**
     * @return bool|null
     */
    public function savingAccount(): bool
    {
        $savingAccount = $this->json('payment.checkInformation.savingAccount', false);
        return filter_var($savingAccount, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return string|null
     */
    public function socialSecurityLast4(): ?string
    {
        return (string) (
            $this->json('payment.checkInformation.socialSecurityLast4', null)
        );
    }

    /**
     * @return string|null
     */
    public function label(): ?string
    {
        return (string) (
            $this->json('payment.checkInformation.label', null)
        );
    }
}
