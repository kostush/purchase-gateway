<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Mgpg;

use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;

class NgResponseService
{
    /**
     * @param array $charge
     * @return bool
     */
    public function hasRebillOnTaxes(array $charge): bool
    {
        return isset($charge['tax']) && isset($charge['tax']['rebillAmount'])
               && !empty($charge['rebillDays']) && !empty($charge['rebillAmount']);
    }

    /**
     * @param array $charge
     * @return bool
     */
    public function hasTaxes(array $charge): bool
    {
        return isset($charge['tax']) && isset($charge['tax']['initialAmount']);
    }

    /**
     * @param array $charge
     * @return bool
     */
    public function hasRebill(array $charge): bool
    {
        return isset($charge['rebillDays']) && isset($charge['rebillAmount'])
               && !empty($charge['rebillDays']) && !empty($charge['rebillAmount']);
    }

    /**
     * @param array $ngPayload
     * @return bool
     */
    public function isSubscription(array $ngPayload): bool
    {
        return $ngPayload['initialDays'] > 0;
    }

    /**
     * @return bool
     */
    public function displayChargedAmount(array $tax): bool
    {
        if(isset($tax['taxType']) && strtolower($tax['taxType']) == 'vat') {
            return true;
        }
        return false;
    }

    /**
     * @param $siteId
     * @param $bundleId
     * @param $addonId
     * @return string
     */
    public function selectedCrossSaleCompositeKey($siteId, $bundleId, $addonId): string
    {
        return "$siteId#$bundleId#$addonId";
    }

    /**
     * Hybrid payments are the ones where we show our own gateway for the user to fill his data, but afterwards
     * we send it to 3rd party.
     * @param string $paymentMethod
     * @param string $paymentType
     * @return bool
     */
    public function isHybridPayment(string $paymentType, string $paymentMethod): bool
    {
        if ($paymentType == PaymentType::BANKTRANSFER) {
            // QYSSO
            if ($paymentMethod == PaymentMethod::ZELLE) {
                return true;
            }

            // Centrobill
            if ($paymentMethod == PaymentMethod::SEPADIRECTDEBIT) {
                return true;
            }
        }

        if ($paymentType == PaymentType::GIFTCARDS) {
            // Paygarden
            if ($paymentMethod == PaymentMethod::GIFTCARDS) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $paymentType
     * @param string $paymentMethod
     * @return bool
     */
    public function isCryptoPayment(string $paymentType, string $paymentMethod) : bool
    {
        return ($paymentType === PaymentType::CRYPTOCURRENCY && $paymentMethod === PaymentMethod::CRYPTOCURRENCY);
    }

    /**
     * Return request properties to map to MGPG request.
     * @return string[]
     */
    public static function getLegacyMappingProperties(): array
    {
        return [
            'legacyProductId'                 => 'int',
            'legacyMemberId'                  => 'int',
            'bypassUi'                        => 'boolean',
            'hideUsernameField'               => 'boolean',
            'hideUsernamePasswordFields'      => 'boolean',
            'hideUsernamePasswordEmailFields' => 'boolean',
            'hidePasswordFromEmail'           => 'boolean',
            'requireActiveParent'             => 'boolean',
            'parentSubscriptionId'            => 'string',
            'templateId'                      => 'string',
            'packageId'                       => 'string',
            'subSiteId'                       => 'string',
            'crossSellType'                   => 'string',
            'crossSellDefaultValue'           => 'boolean'
        ];
    }

}
