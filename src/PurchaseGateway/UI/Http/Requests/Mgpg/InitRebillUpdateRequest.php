<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

class InitRebillUpdateRequest extends Request
{
    /** @var array */
    protected $rules = [
        'siteId'                                                => 'required|uuid',
        'bundleId'                                              => 'required|uuid',
        'addonId'                                               => 'required|uuid',
        'currency'                                              => 'required|string',
        'clientIp'                                              => 'required|ip',
        'paymentType'                                           => 'required|string',
        'paymentMethod'                                         => 'required|string',
        'clientCountryCode'                                     => 'required|string',
        'amount'                                                => 'required|numeric',
        'initialDays'                                           => 'required|integer|min:0|max:10000',
        'rebillDays'                                            => 'required|integer|min:0|max:10000',
        'rebillAmount'                                          => 'required|numeric',
        'memberId'                                              => 'required|uuid',
        'usingMemberProfile'                                    => 'bool',
        'itemId'                                                => 'required|uuid',
        'businessTransactionOperation'                          => 'required|string',
        'legacyMapping.data.legacyMemberId'                     => 'filled|integer_only',
        'legacyMapping.data.legacyProductId'                    => 'filled|integer_only',
        'redirectUrl'                                           => 'nullable|string|url|max:300',
        'postbackUrl'                                           => 'nullable|string|url|max:300',
        'tax'                                                   => 'array',
        'tax.initialAmount'                                     => 'required_with:tax|array',
        'tax.initialAmount.beforeTaxes'                         => 'required_with:tax.initialAmount|numeric',
        'tax.initialAmount.taxes'                               => 'required_with:tax.initialAmount|numeric',
        'tax.initialAmount.afterTaxes'                          => 'required_with:tax.initialAmount|numeric|same:amount',
        'tax.rebillAmount'                                      => 'array',
        'tax.rebillAmount.beforeTaxes'                          => 'required_with:tax.rebillAmount|numeric',
        'tax.rebillAmount.taxes'                                => 'required_with:tax.rebillAmount|numeric',
        'tax.rebillAmount.afterTaxes'                           => 'required_with:tax.rebillAmount|numeric|same:rebillAmount',
        'tax.taxApplicationId'                                  => 'string',
        'tax.taxName'                                           => 'string',
        'tax.taxRate'                                           => 'numeric',
        'tax.custom'                                            => 'string',
        'tax.taxType'                                           => 'string',
        'overrides'                                             => 'array',
        'dws'                                                   => 'array',
        'addRemainingDays'                                      => 'bool',
        'legacyMapping'                                         => 'array',
        'legacyMapping.legacyMemberId'                          => 'required_with:legacyMapping|numeric',
        'legacyMapping.legacyProductId'                         => 'required_with:legacyMapping|numeric',
    ];

    /**
     * @var array
     */
    protected $messages = [
        'uuid' => 'The :attribute value :input is not uuid.'
    ];

    public function getOverrides(): ?array
    {
        return $this->get('overrides');
    }

    /**
     * @return Site
     */
    public function getSite(): Site
    {
        return $this->get('site');
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return strtoupper((string) $this->json('currency'));
    }

    /**
     * @return string
     */
    public function getBundleId(): string
    {
        return (string) $this->json('bundleId');
    }

    /**
     * @return string
     */
    public function getAddonId(): string
    {
        return (string) $this->json('addonId');
    }

    /**
     * @return string
     */
    public function getClientIp(): string
    {
        return (string) $this->json('clientIp');
    }

    /**
     * @return string
     */
    public function getBusinessTransactionOperation(): string
    {
        return (string) $this->json('businessTransactionOperation');
    }

    /**
     * @return string
     */
    public function getPaymentType(): string
    {
        return (string) $this->json('paymentType');
    }

    /**
     * @return string
     */
    public function getClientCountryCode(): string
    {
        return (string) $this->json('clientCountryCode');
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->json('amount');
    }

    /**
     * @return int
     */
    public function getInitialDays(): int
    {
        return (int) $this->json('initialDays');
    }

    /**
     * @return null|int
     */
    public function getRebillDays(): ?int
    {
        return (int) $this->json('rebillDays', null);
    }

    /**
     * @return null|float
     */
    public function getRebillAmount(): ?float
    {
        return (float) $this->json('rebillAmount', null);
    }

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->attributes->get('sessionId');
    }

    /**
     * @return string|null
     */
    public function getAtlasCode(): ?string
    {
        return (string) $this->json('atlasCode', null);
    }

    /**
     * @return string|null
     */
    public function getAtlasData(): ?string
    {
        return (string) $this->json('atlasData', null);
    }

    /**
     * @return int
     */
    public function getPublicKeyIndex(): int
    {
        return $this->attributes->get('publicKeyId');
    }

    /**
     * @return array
     */
    public function getTax(): array
    {
        return $this->json('tax', []);
    }

    /**
     * @return ?string
     */
    public function getTaxApplicationId(): ?string
    {
        return $this->json('tax.taxApplicationId', null);
    }

    /**
     * @return ?string
     */
    public function getTaxName(): ?string
    {
        return $this->json('tax.taxName', null);
    }

    /**
     * @return float|null
     */
    public function getTaxRate()
    {
        return (float) $this->json('tax.taxRate', null);
    }

    /**
     * @return string|null
     */
    public function getTaxCustom(): ?string
    {
        return $this->json('tax.custom', null);
    }

    /**
     * @return string|null
     */
    public function getTaxType(): ?string
    {
        return $this->json('tax.type', null);
    }

    /**
     * @return array
     */
    public function getTaxInitialAmount(): array
    {
        return $this->json('tax.initialAmount', []);
    }

    /**
     * @return array
     */
    public function getTaxRebillAmount()
    {
        return $this->json('tax.rebillAmount', []);
    }

    /**
     * @return null|float
     */
    public function getInitialAmountBeforeTaxes()
    {
        return (float) $this->json('tax.initialAmount.beforeTaxes', null);
    }

    /**
     * @return float|null
     */
    public function getInitialAmountAfterTaxes()
    {
        return (float) $this->json('tax.initialAmount.afterTaxes', null);
    }

    /**
     * @return null|float
     */
    public function getInitialAmountTaxes()
    {
        return (float) $this->json('tax.initialAmount.taxes', null);
    }

    /**
     * @return float|null
     */
    public function getRebillAmountBeforeTaxes()
    {
        return (float) $this->json('tax.rebillAmount.beforeTaxes', null);
    }

    /**
     * @return float|null
     */
    public function getRebillAmountAfterTaxes()
    {
        return (float) $this->json('tax.rebillAmount.afterTaxes', null);
    }

    /**
     * @return float|null
     */
    public function getRebillAmountTaxes()
    {
        return (float) $this->json('tax.rebillAmount.taxes', null);
    }

    /**
     * @return string|null
     */
    public function getMemberId(): ?string
    {
        return $this->get('memberId', null);
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->get('itemId');
    }

    /**
     * @return string|null
     */
    public function getForceCascade(): ?string
    {
        return $this->header('x-force-cascade');
    }

    /**
     * @return bool
     */
    public function getSkipVoid(): bool
    {
        return filter_var($this->header('x-skip-void', false), FILTER_VALIDATE_BOOLEAN);
    }


    /**
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->get('paymentMethod');
    }

    /**
     * @return string|null
     */
    public function getTrafficSource(): ?string
    {
        return $this->get('trafficSource');
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->get('redirectUrl', null);
    }

    /**
     * @return string|null
     */
    public function getPostbackUrl(): ?string
    {
        return $this->get('postbackUrl');
    }

    /**
     * @return array
     */
    public function getDws(): array
    {
        return $this->get('dws', []);
    }

    /**
     * @return bool
     */
    public function getAddRemainingDays(): bool
    {
        return $this->json('addRemainingDays', false);
    }

    /**
     * @return bool
     */
    public function getUsingMemberProfile(): bool
    {
        return $this->json('usingMemberProfile', true);
    }
    
    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }
}
