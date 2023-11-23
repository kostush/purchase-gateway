<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests;

use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class InitPurchaseRequest extends Request
{
    /** @var array */
    protected $rules = [
        'siteId'                                                => 'required|uuid',
        'bundleId'                                              => 'required|uuid',
        'addonId'                                               => 'required|uuid',
        'currency'                                              => 'required|string',
        'clientIp'                                              => 'required|ip',
        'paymentType'                                           => 'required|string',
        'paymentMethod'                                         => 'nullable|string',
        'clientCountryCode'                                     => 'required|string',
        'amount'                                                => 'required|numeric',
        'initialDays'                                           => 'required|integer|min:0|max:10000',
        'rebillDays'                                            => 'integer|min:0|max:10000',
        'rebillAmount'                                          => 'numeric',
        'isTrial'                                               => 'bool',
        'memberId'                                              => 'required_with:subscriptionId,entrySiteId|uuid',
        'subscriptionId'                                        => 'nullable|uuid',
        'legacyMapping.data.legacyMemberId'                     => 'filled|integer_only',
        'legacyMapping.data.legacyProductId'                    => 'filled|integer_only',
        'entrySiteId'                                           => 'nullable|uuid',
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
        'crossSellOptions'                                      => 'array',
        'crossSellOptions.*.bundleId'                           => 'required_with:crossSellOptions.*|uuid',
        'crossSellOptions.*.addonId'                            => 'required_with:crossSellOptions.*|uuid',
        'crossSellOptions.*.siteId'                             => 'required_with:crossSellOptions.*|uuid',
        'crossSellOptions.*.tax'                                => 'array',
        'crossSellOptions.*.initialDays'                        => 'required_with:crossSellOptions.*|integer|min:0|max:10000',
        'crossSellOptions.*.amount'                             => 'required_with:crossSellOptions.*|numeric',
        'crossSellOptions.*.isTrial'                            => 'bool',
        'crossSellOptions.*.legacyMapping.data.legacyMemberId'  => 'filled|integer_only',
        'crossSellOptions.*.legacyMapping.data.legacyProductId' => 'filled|integer_only',
        'crossSellOptions.*.rebillDays'                         => 'integer|min:0|max:10000',
        'crossSellOptions.*.rebillAmount'                       => 'numeric',
        'crossSellOptions.*.tax.initialAmount'                  => 'required_with:crossSellOptions.*.tax|array',
        'crossSellOptions.*.tax.initialAmount.beforeTaxes'      => 'required_with:crossSellOptions.*.tax.initialAmount|numeric',
        'crossSellOptions.*.tax.initialAmount.taxes'            => 'required_with:crossSellOptions.*.tax.initialAmount|numeric',
        'crossSellOptions.*.tax.initialAmount.afterTaxes'       => 'required_with:crossSellOptions.*.tax.initialAmount|numeric|same:crossSellOptions.*.amount',
        'crossSellOptions.*.tax.rebillAmount'                   => 'array',
        'crossSellOptions.*.tax.rebillAmount.beforeTaxes'       => 'required_with:crossSellOptions.*.tax.rebillAmount|numeric',
        'crossSellOptions.*.tax.rebillAmount.taxes'             => 'required_with:crossSellOptions.*.tax.rebillAmount|numeric',
        'crossSellOptions.*.tax.rebillAmount.afterTaxes'        => 'required_with:crossSellOptions.*.tax.rebillAmount|numeric|same:crossSellOptions.*.rebillAmount',
        'crossSellOptions.*.tax.taxApplicationId'               => 'string',
        'crossSellOptions.*.tax.taxName'                        => 'string',
        'crossSellOptions.*.tax.taxType'                        => 'string',
        'crossSellOptions.*.tax.taxRate'                        => 'numeric',
        'crossSellOptions.*.tax.custom'                         => 'string',
        'overrides'                                             => 'array',
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
     * @return int
     */
    public function getRebillDays(): int
    {
        return (int) $this->json('rebillDays');
    }

    /**
     * @return float
     */
    public function getRebillAmount(): float
    {
        return (float) $this->json('rebillAmount');
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
     * @return array
     */
    public function getCrossSales(): array
    {
        return $this->json('crossSellOptions', []);
    }

    /**
     * @return bool
     */
    public function getIsTrial(): bool
    {
        return (bool) $this->json('isTrial', false);
    }

    /**
     * @return string|null
     */
    public function getMemberId(): ?string
    {
        return $this->get('memberId', null);
    }

    /**
     * @return string|null
     */
    public function getSubscriptionId(): ?string
    {
        return $this->get('subscriptionId', null);
    }

    /**
     * @return string|null
     */
    public function getEntrySiteId(): ?string
    {
        return $this->get('entrySiteId', null);
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
     * @return string|null
     */
    public function getPaymentMethod(): ?string
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
        return $this->gecgfht('postbackUrl');
    }

    /**
     * @return array
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function getOtherDataPaygarden(): array
    {
        return $this->json('otherData.paygarden', null);
    }
}
