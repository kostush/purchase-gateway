<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;

class SessionVersionConverter
{
    public const LATEST_VERSION = 28;

    /**
     * @param array $sessionPayload Session Payload The session payload array
     *
     * @return array
     *
     * @throws SessionConversionException
     * @throws \Exception
     */
    public function convert(array $sessionPayload): array
    {
        if (!isset($sessionPayload['version'])) {
            $sessionPayload['version'] = 10;
        }

        while ($sessionPayload['version'] < self::LATEST_VERSION) {
            switch ($sessionPayload['version']) {
                case 10:
                    $sessionPayload = $this->convertFromVersion10($sessionPayload);
                    break;
                case 11:
                    $sessionPayload = $this->convertFromVersion11($sessionPayload);
                    break;
                case 12:
                    $sessionPayload = $this->convertFromVersion12($sessionPayload);
                    break;
                case 13:
                    $sessionPayload = $this->convertFromVersion13($sessionPayload);
                    break;
                case 14:
                    $sessionPayload = $this->convertFromVersion14($sessionPayload);
                    break;
                case 15:
                    $sessionPayload = $this->convertFromVersion15($sessionPayload);
                    break;
                case 16:
                    $sessionPayload = $this->convertFromVersion16($sessionPayload);
                    break;
                case 17:
                    $sessionPayload = $this->convertFromVersion17($sessionPayload);
                    break;
                case 18:
                    $sessionPayload = $this->convertFromVersion18($sessionPayload);
                    break;
                case 19:
                    $sessionPayload = $this->convertFromVersion19($sessionPayload);
                    break;
                case 20:
                    $sessionPayload = $this->convertFromVersion20($sessionPayload);
                    break;
                case 21:
                    $sessionPayload = $this->convertFromVersion21($sessionPayload);
                    break;
                case 22:
                    $sessionPayload = $this->convertFromVersion22($sessionPayload);
                    break;
                case 23:
                    $sessionPayload = $this->convertFromVersion23($sessionPayload);
                    break;
                case 24:
                    $sessionPayload = $this->convertFromVersion24($sessionPayload);
                    break;
                case 25:
                    $sessionPayload = $this->convertFromVersion25($sessionPayload);
                    break;
                case 26:
                    $sessionPayload = $this->convertFromVersion26($sessionPayload);
                    break;
                case 27:
                    $sessionPayload = $this->convertFromVersion27($sessionPayload);
                    break;
                default:
                    Log::emergency('Session could not be converted!', ['sessionPayload' => $sessionPayload]);
                    throw new SessionConversionException();
            }
            $sessionPayload['version']++;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session Payload
     * @return array
     */
    private function convertFromVersion10(array $sessionPayload): array
    {
        /**
         * If there is no fraudAdvice, return the current response
         */
        if (!isset($sessionPayload['fraudAdvice'])) {
            return $sessionPayload;
        }
        /**
         * If the current fraudAdvice is empty, return empty recommendation
         */
        if (empty($sessionPayload['fraudAdvice'])) {
            $sessionPayload['fraudRecommendation'] = null;
            return $sessionPayload;
        }

        $sessionPayload['fraudAdvice']['forceThreeD']       = false;
        $sessionPayload['fraudAdvice']['detectThreeDUsage'] = false;

        /**
         * If we have the fraudAdvice, map it to the new fraudRecommendation
         */
        $sessionPayload['fraudRecommendation'] = FraudIntegrationMapper::mapFraudAdviceArrayToFraudRecommendationArray(
            $sessionPayload['fraudAdvice']
        );

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion11(array $sessionPayload): array
    {
        if (!array_key_exists('redirectUrl', $sessionPayload)) {
            $sessionPayload['redirectUrl'] = null;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion12(array $sessionPayload): array
    {
        if (!array_key_exists('currency', $sessionPayload)) {
            $sessionPayload['currency'] = $sessionPayload['cascade']['currencyCode'];
        }

        unset($sessionPayload['cascade']['currencyCode']);

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion13(array $sessionPayload): array
    {
        foreach ($sessionPayload['initializedItemCollection'] as &$item) {
            foreach ($item['transactionCollection'] as &$transaction) {
                if (!isset($transaction['newCCUsed'])) {
                    $transaction['newCCUsed'] = false;
                }

                if (!isset($transaction['billerName'])) {
                    $transaction['billerName'] = RocketgateBiller::BILLER_NAME;
                }

                if (!isset($transaction['acs'])) {
                    $transaction['acs'] = null;
                }

                if (!isset($transaction['pareq'])) {
                    $transaction['pareq'] = null;
                }
            }
        }

        if (!empty($sessionPayload['paymentTemplateCollection'])) {
            foreach ($sessionPayload['paymentTemplateCollection'] as &$paymentTemplate) {
                if (!empty($paymentTemplate['billerName'])) {
                    continue;
                }

                $paymentTemplate['billerName'] = RocketgateBiller::BILLER_NAME;
            }
        }

        if (empty($sessionPayload['cascade']['billers'])) {
            $sessionPayload['cascade']['billers'] = [RocketgateBiller::BILLER_NAME];
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion14(array $sessionPayload): array
    {
        if (!isset($sessionPayload['paymentMethod'])) {
            $sessionPayload['paymentMethod'] = null;
        }

        if (!isset($sessionPayload['trafficSource'])) {
            $sessionPayload['trafficSource'] = 'ALL';
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion15(array $sessionPayload): array
    {
        if (!array_key_exists('postbackUrl', $sessionPayload)) {
            $sessionPayload['postbackUrl'] = null;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     * @throws \Exception
     */
    private function convertFromVersion16(array $sessionPayload): array
    {
        foreach ($sessionPayload['initializedItemCollection'] as &$item) {
            if (!isset($item['isCrossSaleSelected'])) {
                $item['isCrossSaleSelected'] = false;
            }
        }

        if (!isset($sessionPayload['purchaseId'])) {
            $sessionPayload['purchaseId'] = null;
        }

        if (!isset($sessionPayload['cascade']['isDefaultBillerUsed'])) {
            $sessionPayload['cascade']['isDefaultBillerUsed'] = false;
        }

        if (!isset($sessionPayload['fraudAdvice']['forceThreeDOnInit'])) {
            $sessionPayload['fraudAdvice']['forceThreeDOnInit'] = false;
        }

        if (!isset($sessionPayload['fraudAdvice']['forceThreeDOnProcess'])) {
            $sessionPayload['fraudAdvice']['forceThreeDOnProcess'] = false;
        }


        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     * @throws \Exception
     */
    private function convertFromVersion17(array $sessionPayload): array
    {
        $sessionPayload['fraudRecommendationCollection'] = [];
        if (!empty($sessionPayload['fraudRecommendation'])) {
            $sessionPayload['fraudRecommendationCollection'] = [
                $sessionPayload['fraudRecommendation']
            ];
        }

        unset($sessionPayload['fraudRecommendation']);

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion18(array $sessionPayload): array
    {
        foreach ($sessionPayload['initializedItemCollection'] as &$item) {
            foreach ($item['transactionCollection'] as &$transaction) {
                if (!isset($transaction['redirectUrl'])) {
                    $transaction['redirectUrl'] = null;
                }

                if (!isset($transaction['crossSales'])) {
                    $transaction['crossSales'] = null;
                }
            }
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion19(array $sessionPayload): array
    {
        if (!isset($sessionPayload['cascade']['currentBillerSubmit'])) {
            $sessionPayload['cascade']['currentBillerSubmit'] = 0;
        }

        $sessionPayload['cascade']['currentBiller'] = $sessionPayload['cascade']['biller'];

        unset($sessionPayload['cascade']['biller']);
        unset($sessionPayload['cascade']['isDefaultBillerUsed']);

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion20(array $sessionPayload): array
    {
        if (!isset($sessionPayload['cascade']['currentBillerPosition'])) {
            $sessionPayload['cascade']['currentBillerPosition'] = 0;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion21(array $sessionPayload): array
    {
        foreach ($sessionPayload['initializedItemCollection'] as &$item) {
            foreach ($item['transactionCollection'] as &$transaction) {
                if (!isset($transaction['isNsf'])) {
                    $transaction['isNsf'] = null;
                }
            }
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion22(array $sessionPayload): array
    {
        if (!isset($sessionPayload['cascade']['removedBillersFor3DS'])) {
            $sessionPayload['cascade']['removedBillersFor3DS'] = null;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion23(array $sessionPayload): array
    {
        foreach ($sessionPayload['initializedItemCollection'] as &$item) {
            foreach ($item['transactionCollection'] as &$transaction) {
                if (!isset($transaction['deviceCollectionUrl'])) {
                    $transaction['deviceCollectionUrl'] = null;
                }

                if (!isset($transaction['deviceCollectionJwt'])) {
                    $transaction['deviceCollectionJwt'] = null;
                }

                if (!isset($transaction['deviceFingerprintId'])) {
                    $transaction['deviceFingerprintId'] = null;
                }

                if (!isset($transaction['threeDStepUpUrl'])) {
                    $transaction['threeDStepUpUrl'] = null;
                }

                if (!isset($transaction['threeDStepUpJwt'])) {
                    $transaction['threeDStepUpJwt'] = null;
                }

                if (!isset($transaction['md'])) {
                    $transaction['md'] = null;
                }

                if (!isset($transaction['threeDFrictionless'])) {
                    $transaction['threeDFrictionless'] = false;
                }

                if (!isset($transaction['threeDVersion'])) {
                    $transaction['threeDVersion'] = null;
                }
            }
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion24(array $sessionPayload): array
    {
        if (!isset($sessionPayload['paymentTemplateId'])) {
            $sessionPayload['paymentTemplateId'] = null;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     * @throws \Exception
     */
    private function convertFromVersion25(array $sessionPayload): array
    {
        if (!empty($sessionPayload['paymentTemplateCollection'])) {
            foreach ($sessionPayload['paymentTemplateCollection'] as &$paymentTemplate) {
                if (!empty($paymentTemplate['createdAt'])) {
                    continue;
                }

                $paymentTemplate['createdAt'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
            }
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion26(array $sessionPayload): array
    {
        if (!isset($sessionPayload['skipVoid'])) {
            $sessionPayload['skipVoid'] = false;
        }

        return $sessionPayload;
    }

    /**
     * @param array $sessionPayload Session payload
     * @return array
     */
    private function convertFromVersion27(array $sessionPayload): array
    {
        if (!isset($sessionPayload['creditCardWasBlacklisted'])) {
            $sessionPayload['creditCardWasBlacklisted'] = false;
        }

        return $sessionPayload;
    }
}
