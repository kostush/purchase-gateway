<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg;

use \ProBillerNG\PurchaseGateway\UI\Http\Requests\InitPurchaseRequest as BaseInitPurchaseRequest;

class InitPurchaseRequest extends BaseInitPurchaseRequest
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
        'memberId'                                              => 'string',
        'usingMemberProfile'                                    => 'bool',
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
        'crossSellOptions.*.tax.taxName'                        => 'string',
        'crossSellOptions.*.tax.taxType'                        => 'string',
        'crossSellOptions.*.tax.taxRate'                        => 'numeric',
        'crossSellOptions.*.tax.custom'                         => 'string',
        'crossSellOptions.*.otherData'                          => 'array',
        'crossSellOptions.*.otherData.paygarden'                => 'array|required_if:paymentType,giftcards',
        'crossSellOptions.*.otherData.paygarden.data'           => 'array',
        'crossSellOptions.*.otherData.paygarden.data.credit'    => 'numeric',
        'crossSellOptions.*.otherData.paygarden.data.sku'       => 'string',
        'crossSellOptions.*.entitlements'                       => 'array',
        'overrides'                                             => 'array',
        'tax.taxApplicationId'                                  => 'required_with:tax|uuid',
        'crossSellOptions.*.tax.taxApplicationId'               => 'required_with:tax|uuid',
        'dws'                                                   => 'array',
        'dws.maxMind'                                           => 'array',
        'dws.maxMind.x-geo-city'                                => 'string|required_if:paymentType,checks',
        'dws.maxMind.x-geo-postal-code'                         => 'string|required_if:paymentType,checks',
        'otherData'                                             => 'array',
        'otherData.paygarden'                                   => 'array|required_if:paymentType,giftcards',
        'otherData.paygarden.data'                              => 'array',
        'otherData.paygarden.data.credit'                       => 'numeric',
        'otherData.paygarden.data.sku'                          => 'string',
        'entitlements'                                          => 'array',
    ];

    /**
     * @return array
     */
    public function getDws(): array
    {
        return $this->get('dws', []);
    }

    /**
     * @return string|null
     */
    public function getMemberId(): ?string
    {
        return (string) $this->get('memberId');
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
    public function getEntitlementsFromClient(): array
    {
        return $this->json('entitlements', []);
    }
}
