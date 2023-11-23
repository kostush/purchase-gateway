<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\Purchase\Process\AdditionalPurchaseItem;
use ProbillerMGPG\Purchase\Process\MemberInfo;
use ProbillerMGPG\Purchase\Process\MemberProfile;
use ProbillerMGPG\Purchase\Process\Payment;
use ProbillerMGPG\Purchase\Process\PurchaseProcessRequest as MGPGPurchaseProcessRequest;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownPaymentInformationException;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest;

class ProcessPurchaseNgToMgpgService
{
    /**
     * @var NgResponseService
     */
    protected $ngResponseService;

    /**
     * @var TokenGenerator
     */

    protected $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var PaymentInformationFactory
     */
    private $paymentInformationFactory;

    /**
     * ProcessPurchaseController constructor.
     * @param NgResponseService $ngResponseService
     * @param TokenGenerator $tokenGenerator
     * @param CryptService $cryptService
     * @param PaymentInformationFactory $paymentInformationFactory
     */
    public function __construct(
        NgResponseService $ngResponseService,
        TokenGenerator $tokenGenerator,
        CryptService $cryptService,
        PaymentInformationFactory $paymentInformationFactory
    ) {
        $this->ngResponseService         = $ngResponseService;
        $this->tokenGenerator            = $tokenGenerator;
        $this->cryptService              = $cryptService;
        $this->paymentInformationFactory = $paymentInformationFactory;
    }

    /**
     * Based on NG site/bundle/addon combination provided, fetch associate chargeId in the map that
     * was generated on init step.
     * @param ProcessPurchaseRequest $r Input Request from NG Process
     * @param array                  $crossSaleChargeIdMap
     * @return array
     * @throws Exception
     */
    public function selectedChargeIds(
        ProcessPurchaseRequest $r,
        array $crossSaleChargeIdMap
    ): array {
        $selectedChargeIds = [];

        foreach ($r->selectedCrossSells() as $crossSell) {
            $compositeKey = $this->ngResponseService->selectedCrossSaleCompositeKey(
                $crossSell['siteId'] ?? null,
                $crossSell['bundleId'] ?? null,
                $crossSell['addonId'] ?? null
            );

            if (isset($crossSaleChargeIdMap[$compositeKey]) == false) {
                Log::info('Selected cross-sale does not match from originating init call.');
                continue;
            }

            $selectedChargeIds[] = $crossSaleChargeIdMap[$compositeKey];
        }

        return $selectedChargeIds;
    }

    /**
     * @param ProcessPurchaseRequest $ngRequest The NG Input Payload
     * @param array $selectedChargeIds
     * @return MGPGPurchaseProcessRequest
     * @throws Exception
     */
    public function translate(
        ProcessPurchaseRequest $ngRequest,
        array $selectedChargeIds
    ): MGPGPurchaseProcessRequest {

        $additionalPurchaseItems = [];

        foreach ($selectedChargeIds as $chargeId) {
            $additionalPurchaseItems[] = new AdditionalPurchaseItem($chargeId);
        }

        $memberInfo = null;

        if ($ngRequest->memberEmail() && $ngRequest->memberFirstName() && $ngRequest->memberLastName()) {
            $memberInfo = new MemberInfo(
                $ngRequest->memberEmail(),
                $ngRequest->memberFirstName(),
                $ngRequest->memberLastName(),
                $ngRequest->memberCountryCode(),
                $ngRequest->memberZipCode(),
                $ngRequest->memberAddress1(),
                $ngRequest->memberAddress2(),
                $ngRequest->memberCity(),
                $ngRequest->memberPhone()
            );
        }

        return new MGPGPurchaseProcessRequest(
            $memberInfo,
            new Payment($this->paymentInformationFactory->create($ngRequest)),
            $additionalPurchaseItems,
            $ngRequest->ndWidgetData(),
            new MemberProfile(
                $ngRequest->memberUsername(),
                $ngRequest->memberPassword()
            )
        );
    }
}
