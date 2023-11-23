<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\CascadeBillersExhausted;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Redirected;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\State;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDLookupPerformed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use ProBillerNG\PurchaseGateway\Domain\Model\ThreeDAuthenticateUrl;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\ThreeDDeviceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class NextActionProcessFactory implements NextActionFactory
{
    /**
     * @param State            $state               Purchase State.
     * @param string|null      $authenticateUrl     Authenticate Url.
     * @param ThirdParty|null  $thirdParty          Third party.
     * @param bool|null        $redirectUrlExist    Redirect url exist.
     * @param string|null      $deviceCollectionUrl Device collection url.
     * @param string|null      $deviceCollectionJwt Device collection jwt.
     * @param Transaction|null $transaction         Transaction
     * @param string|null      $resolution          Resolution
     * @param string|null      $reason              Reason
     * @return NextAction
     * @throws Exception
     * @throws InvalidStateException
     */
    public static function create(
        State $state,
        ?string $authenticateUrl = null,
        ?ThirdParty $thirdParty = null,
        ?bool $redirectUrlExist = null,
        $deviceCollectionUrl = null,
        $deviceCollectionJwt = null,
        Transaction $transaction = null,
        ?string $resolution = null,
        ?string $reason = null
    ): NextAction {
        switch ($state) {
            case $state instanceof Pending:
                $threeDPaymentLinkUrl = ($transaction !== null) ? $transaction->threeDPaymentLinkUrl() : null;

                return self::createNextActionInCaseOfPendingState(
                    $authenticateUrl,
                    $deviceCollectionUrl,
                    $deviceCollectionJwt,
                    $threeDPaymentLinkUrl
                );
            case $state instanceof ThreeDLookupPerformed:
                return self::createAuthenticateThreeD(
                    $authenticateUrl,
                    $transaction
                );
            case $state instanceof Processed:
                return FinishProcess::create($resolution, $reason);

            case $state instanceof BlockedDueToFraudAdvice:
                return RestartProcess::create();

            case $state instanceof Valid:
                return self::createNextActionInCaseOfValidState($thirdParty, $redirectUrlExist);

            case $state instanceof CascadeBillersExhausted:
                return RedirectToFallbackProcessor::create();

            case $state instanceof Redirected:
                return WaitForReturn::create();

            default:
                throw new InvalidStateException();
        }
    }

    /**
     * @param string           $authenticateUrl Authenticate URL.
     * @param Transaction|null $transaction     Last transaction
     * @return AuthenticateThreeD
     */
    private static function createAuthenticateThreeD(
        string $authenticateUrl,
        ?Transaction $transaction
    ): AuthenticateThreeD {
        $threeDAuthenticateUrl = ThreeDAuthenticateUrl::create($authenticateUrl);

        return AuthenticateThreeD::create($threeDAuthenticateUrl, $transaction);
    }

    /**
     * @param string|null $deviceCollectionUrl Device collection url.
     * @param string|null $deviceCollectionJwt Device collection jwt.
     * @return DeviceDetectionThreeD
     */
    private static function createDeviceDetectionThreeD(
        ?string $deviceCollectionUrl,
        ?string $deviceCollectionJwt
    ): DeviceDetectionThreeD {
        $threeDDeviceCollection = ThreeDDeviceCollection::create(
            $deviceCollectionUrl,
            $deviceCollectionJwt
        );

        return DeviceDetectionThreeD::create($threeDDeviceCollection);
    }

    /**
     * @param ThirdParty|null $thirdParty       Third party.
     * @param bool|null       $redirectUrlExist Redirect url exist.
     * @return NextAction
     */
    private static function createNextActionInCaseOfValidState(
        ?ThirdParty $thirdParty = null,
        ?bool $redirectUrlExist = null
    ): NextAction {
        if ($thirdParty === null) {
            return RenderGateway::create();
        }

        if (!$redirectUrlExist) {
            return RestartProcess::create('Missing redirect url.');
        }

        return RedirectToUrl::create($thirdParty);
    }

    /**
     * @param string|null      $authenticateUrl      Authenticate url.
     * @param string|null      $deviceCollectionUrl  Device collection url.
     * @param string|null      $deviceCollectionJwt  Device collection jwt.
     * @param string|null      $threeDPaymentLinkUrl The return url
     * @param Transaction|null $transaction          Transaction
     * @return NextAction
     */
    private static function createNextActionInCaseOfPendingState(
        ?string $authenticateUrl,
        ?string $deviceCollectionUrl,
        ?string $deviceCollectionJwt,
        ?string $threeDPaymentLinkUrl,
        Transaction $transaction = null
    ): NextAction {
        if ($deviceCollectionUrl === null || $deviceCollectionJwt === null) {
            if ($threeDPaymentLinkUrl !== null) {
                return RedirectToUrl::create(ThirdParty::create($threeDPaymentLinkUrl));
            }
            return self::createAuthenticateThreeD($authenticateUrl, $transaction);
        }

        return self::createDeviceDetectionThreeD($deviceCollectionUrl, $deviceCollectionJwt);
    }
}
