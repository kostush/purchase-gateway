<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event\Versioning;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\DomainEventConversionException;
use ProBillerNG\PurchaseGateway\Application\Services\DomainEventVersionConverterDefinition;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;

class PurchaseProcessedConverter implements DomainEventVersionConverterDefinition
{
    /**
     * @return int
     */
    public function latestVersion(): int
    {
        return PurchaseProcessed::LATEST_VERSION;
    }

    /**
     * @param array $payload The event payload
     * @return array
     * @throws DomainEventConversionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function convert(array $payload): array
    {
        $version = 1;
        if (isset($payload['version']) && $payload['version'] > 0) {
            $version = $payload['version'];
        }

        while ($version < $this->latestVersion()) {
            switch ($version) {
                case 1:
                    $payload = $this->convertFromVersion1($payload);
                    break;
                case 2:
                    $payload = $this->convertFromVersion2($payload);
                    break;
                case 3:
                    $payload = $this->convertFromVersion3($payload);
                    break;
                case 4:
                    $payload = $this->convertFromVersion4($payload);
                    break;
                case 5:
                    $payload = $this->convertFromVersion5($payload);
                    break;
                case 6:
                    $payload = $this->convertFromVersion6($payload);
                    break;
                case 7:
                    $payload = $this->convertFromVersion7($payload);
                    break;
                case 8:
                    $payload = $this->convertFromVersion8($payload);
                    break;
                case 9:
                    $payload = $this->convertFromVersion9($payload);
                    break;
                case 10:
                    $payload = $this->convertFromVersion10($payload);
                    break;
                case 11:
                    $payload = $this->convertFromVersion11($payload);
                    break;
                case 12:
                    $payload = $this->convertFromVersion12($payload);
                    break;
                case 13:
                    $payload = $this->convertFromVersion13($payload);
                    break;
                default:
                    Log::emergency('Session could not be converted!', ['sessionPayload' => $payload]);
                    throw new DomainEventConversionException();
            }

            $version++;
        }

        return $payload;
    }

    /**
     * Added for tax integration unset(amount, rebillAmount), add amounts
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion1(array $payload): array
    {
        if (empty($payload['amounts']) && isset($payload['amount'], $payload['rebill_amount'])) {
            $payload['amounts'] = $this->createAmountsFromRequest($payload['amount'], $payload['rebill_amount']);
            unset($payload['amount'], $payload['rebill_amount']);
        }

        return $payload;
    }

    /**
     * Add back the amount and rebill amount
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion2(array $payload): array
    {
        if (isset($payload['amounts']) && $payload['amounts'] !== null) {
            // inconsistency in old events not handled at the right time. The key is either initialAmount or initial
            $initialAmount           = $payload['amounts']['initialAmount'] ?? $payload['amounts']['initial'];
            $payload['amount']       = $initialAmount['beforeTaxes'];
            // Same for rebill key as above
            $rebillAmount            = $payload['amounts']['rebillAmount'] ?? $payload['amounts']['rebill'];
            $payload['rebillAmount'] = $rebillAmount['beforeTaxes'];

            if ($initialAmount['beforeTaxes'] == $initialAmount['afterTaxes']
                && $rebillAmount['beforeTaxes'] == $rebillAmount['afterTaxes']
            ) {
                unset($payload['amounts']);
            }

        }

        return $payload;
    }

    /**
     * Added for tax integration unset(amount, rebillAmount), add amounts
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion3(array $payload): array
    {
        if (!empty($payload)) {
            unset($payload['version']);

            if (!isset($payload['cross_sale_purchase_data'])) {
                $payload['cross_sale_purchase_data'] = [];
            }

            foreach ($payload['cross_sale_purchase_data'] as $key => $crossSale) {
                $crossSale['transactionCollection'][] = [
                    'transactionId' => $crossSale['transactionId'] ?? null,
                    'state'         => $crossSale['status'] ?? null,
                ];

                $crossSale['tax'] = $crossSale['amounts'] ?? null;
                unset($crossSale['amounts']);

                $crossSale['initialAmount'] = $crossSale['amount'] ?? null;
                unset($crossSale['amount']);

                $crossSale['rebillDays'] = $crossSale['rebillFrequency'] ?? null;
                unset($crossSale['rebillFrequency']);

                $crossSale['initialDays'] = $crossSale['rebillStartDays'] ?? null;
                unset($crossSale['rebillStartDays']);

                $crossSale['addonId'] = $crossSale['addOnId'];
                unset($crossSale['addOnId']);

                $crossSale['isCrossSale'] = true;

                unset($crossSale['transactionId']);

                $payload['cross_sale_purchase_data'][$key] = $crossSale;
            }
            unset($payload['selected_cross_sells']);

            $payload['transaction_collection'][] = [
                'state'         => $payload['status'] ?? null,
                'transactionId' => $payload['transaction_id'] ?? null,
            ];
            unset($payload['transaction_id']);
        }

        return $payload;
    }

    /**
     * @param array $payload The payload
     * @return array
     */
    private function convertFromVersion4(array $payload): array
    {
        $payload['initial_days'] = $payload['rebill_start_days'] ?? null;
        unset($payload['rebill_start_days']);

        return $payload;
    }

    /**
     * Added for tax integration unset(amount, rebillAmount), add amounts
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion5(array $payload): array
    {
        if (!empty($payload)) {
            $payload['entry_site_id']      = null;
            $payload['is_existing_member'] = false;
        }

        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion6(array $payload): array
    {
        if (!empty($payload)) {
            if (!isset($payload['is_trial'])) {
                $payload['is_trial'] = false;
            }
        }

        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion7(array $payload): array
    {
        if (!empty($payload['transaction_collection'])) {
            foreach ($payload['transaction_collection'] as $key => $transaction) {
                if ($transaction['state'] === 'failed') {
                    if (empty($transaction['transactionId'])) {
                        $payload['transaction_collection'][$key]['state'] = 'aborted';
                    } else {
                        $payload['transaction_collection'][$key]['state'] = 'declined';
                    }
                }
            }
        }
        if (empty($payload['item_id']) && !empty($payload['transaction_collection'])) {
            $payload['item_id'] = last($payload['transaction_collection'])['transactionId'] ?? null;
        }

        $cross_sale_purchase_data = $payload['cross_sale_purchase_data'] ?? [];
        foreach ($cross_sale_purchase_data as $key => $crossSale) {
            if (!empty($crossSale['transactionCollection'])) {
                foreach ($crossSale['transactionCollection'] as $keyTrans => $transaction) {
                    if ($transaction['state'] === 'failed') {
                        if (empty($transaction['transactionId'])) {
                            $payload['cross_sale_purchase_data'][$key]
                                ['transactionCollection'][$keyTrans]['state'] = 'aborted';
                        } else {
                            $payload['cross_sale_purchase_data'][$key]
                                ['transactionCollection'][$keyTrans]['state'] = 'declined';
                        }
                    }
                }
            }

            if (empty($crossSale['itemId']) && !empty($crossSale['transactionCollection'])) {
                $payload['cross_sale_purchase_data'][$key]['itemId'] = last(
                    $crossSale['transactionCollection']
                )['transactionId'] ?? null;
            }

            if (empty($crossSale['isTrial'])) {
                $payload['cross_sale_purchase_data'][$key]['isTrial'] = false;
            }
        }

        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion8(array $payload): array
    {
        if (empty($payload['three_d_required'])) {
            $payload['three_d_required'] = false;
        }
        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion9(array $payload): array
    {
        if (empty($payload['is_third_party'])) {
            $payload['is_third_party'] = false;
        }
        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion10(array $payload): array
    {
        if (empty($payload['is_nsf'])) {
            $payload['is_nsf'] = false;
        }
        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion11(array $payload): array
    {
        if (empty($payload['threed_version'])) {
            $payload['threed_version'] = $payload['three_d_required'] ? 1 : null;
        }

        if (empty($payload['threed_frictionless'])) {
            $payload['threed_frictionless'] = false;
        }
        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion12(array $payload): array
    {
        if (empty($payload['is_username_padded'])) {
            $payload['is_username_padded'] = false;
        }
        return $payload;
    }

    /**
     * @param array $payload Session Payload
     * @return array
     */
    private function convertFromVersion13(array $payload): array
    {
        if (empty($payload['skip_void_transaction'])) {
            $payload['skip_void_transaction'] = false;
        }

        return $payload;
    }

    /**
     * @param float|null $amount       Amount
     * @param float|null $rebillAmount Rebill Amount
     * @return array
     */
    private function createAmountsFromRequest(?float $amount, ?float $rebillAmount): array
    {
        return [
            'initialAmount'    => [
                'beforeTaxes' => $amount ?? 0,
                'taxes'       => 0,
                'afterTaxes'  => $amount ?? 0,
            ],
            'rebillAmount'     => [
                'beforeTaxes' => $rebillAmount ?? 0,
                'taxes'       => 0,
                'afterTaxes'  => $rebillAmount ?? 0,
            ],
            'taxApplicationId' => '',
            'taxName'          => '',
            'taxRate'          => 0,
        ];
    }
}
