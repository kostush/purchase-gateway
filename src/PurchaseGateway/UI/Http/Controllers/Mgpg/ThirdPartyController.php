<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ValidationException;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;

abstract class ThirdPartyController extends Controller
{
    protected $mgpgRequestPatternParameters = [
        'invoice' => [],
        'digest' => 'digest',
        'redirectUrl' => 'redirectUrl',
        'nextAction' => []
    ];

    protected $invoiceTranslateMapping = [
        'invoiceId' => 'purchaseId',
        'memberId'  => 'memberId',
        'charges'   => []
        //        'paymentId'     => '',//TODO ask where to get it?
        //        'skuId'         => 'bundleId',
        //        ''              => 'subscriptionId', //TODO ask where to get it?
    ];

    protected $chargeTranslateMapping = [
        'transactionId' => 'transactionId',
        'chargeId'      => 'itemId',
        'status'        => 'success'
    ];

    protected $nextActionTranslateMapping = [
        'type' => 'type'
    ];

    /**
     * @param        $translateFrom
     * @param array  $translateTo
     * @param string $mapFrom
     * @param string $mapTo
     *
     * @return array
     */
    protected function handleTranslateArrays(
        $translateFrom,
        array $translateTo,
        string $mapFrom,
        $mapTo
    ): array //TODO to find a better name for method
    {
        if (is_array($translateFrom) && method_exists($this, $mapFrom)) {
            $translateTo = $this->$mapFrom($translateFrom, $translateTo);
            return $translateTo;
        }

        if (is_string($translateFrom)) {
            $translateTo[$mapTo] = $translateFrom;
        }

        return $translateTo;
    }

    /**
     * @param array $translateFrom
     * @param array $translateTo
     *
     * @return array
     * @throws \Exception
     */
    protected function charges(array $translateFrom, array $translateTo): array
    {
        $primaryCharges = 0;

        foreach ($translateFrom as $charge) {
           if (isset($charge['isPrimaryCharge'])) {

                if($charge['isPrimaryCharge'] == true){
                    $primaryCharges++;
                }

                if($primaryCharges > 1){
                    throw new ValidationException(null,'Charges cannot have more than one primary charge');
                }

                foreach ($this->chargeTranslateMapping as $key => $value) {
                    if ($charge['isPrimaryCharge'] == false) {
                        $translateTo['crossSells'][$value] = $charge[$key];
                    }

                    if ($charge['isPrimaryCharge'] == true) {
                        $translateTo[$value] = $charge[$key];
                    }
                }
            } else {
                Log::error('MGPGThirdPartyAdaptor Charges need one primary charge at least');

                throw new ValidationException(null, 'Charges need one primary charge at least');
            }
        }

        if($primaryCharges == 0){
            throw new ValidationException(null,'Charges cannot have more than one primary charge');
        }

        return $translateTo;
    }

    /**
     * @param array $translateFrom
     * @param array $translateTo
     *
     * @return array
     */
    protected function invoice(array $translateFrom, array $translateTo): array
    {
        foreach ($this->invoiceTranslateMapping as $key => $value) {
            if (isset($translateFrom[$key])) {
                $translateTo = $this->handleTranslateArrays($translateFrom[$key], $translateTo, $key, $value);
            }
        }

        return $translateTo;
    }

    /**
     * @param array $translateFrom
     * @param array $translateTo
     *
     * @return array
     */
    protected function nextAction(array $translateFrom, array $translateTo): array
    {
        foreach ($this->nextActionTranslateMapping as $key => $value) {
            if (isset($translateFrom[$key])) {
                $translateTo['nextAction'][$value] = $translateFrom[$key];
            }
        }

        return $translateTo;
    }

    /**
     * @param string $stringToDecode
     *
     * @return string
     */
    protected function decode(string $stringToDecode): string
    {
        // first check if we're dealing with an actual valid base64 encoded string
        if (($b = base64_decode($stringToDecode, true)) === false) {
            return $stringToDecode;
        }

        // check whether the decoded data could be actual text
        $encoding = mb_detect_encoding($b);
        if (in_array($encoding, ['UTF-8', 'ASCII'])) {
            return base64_decode($stringToDecode);
        }

        return $stringToDecode;
    }
}