<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;

class PurchaseInitCommandHttpDTO implements \JsonSerializable
{
    /** @var array $initResponse */
    private $initResponse;

    /**
     * PurchaseInitCommandHttpDTO constructor.
     * @param PurchaseInitCommandResult $purchaseInitCommandResult Purchase Init Command Result
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    public function __construct(PurchaseInitCommandResult $purchaseInitCommandResult)
    {
        $this->initPurchase($purchaseInitCommandResult);
    }

    /**
     * @param PurchaseInitCommandResult $purchaseInitCommandResult Purchase Init Command Result
     * @return void
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    protected function initPurchase(PurchaseInitCommandResult $purchaseInitCommandResult): void
    {
        $response['sessionId'] = $purchaseInitCommandResult->sessionId();

        if ($mgpgSessionId = $purchaseInitCommandResult->mgpgSessionId()) {
            $response['mgpgSessionId'] = $mgpgSessionId;
        }

        if ($correlationId = $purchaseInitCommandResult->correlationId()) {
            $response['correlationId'] = $correlationId;
        }

        if ($memberId = $purchaseInitCommandResult->memberId()) {
            $response['memberId'] = $memberId;
        }

        if ($subscriptionId = $purchaseInitCommandResult->subscriptionId()) {
            $response['subscriptionId'] = $subscriptionId;
        }

        $response['paymentProcessorType']          = $purchaseInitCommandResult->paymentProcessorType();
        $response['fraudAdvice']                   = $purchaseInitCommandResult->fraudAdvice();
        $response['fraudRecommendation']           = $purchaseInitCommandResult->fraudRecommendation();
        $response['fraudRecommendationCollection'] = [];


        if ($purchaseInitCommandResult->fraudRecommendationCollection() instanceof FraudRecommendationCollection) {
            $response['fraudRecommendationCollection'] = $purchaseInitCommandResult->fraudRecommendationCollection()
                ->toArray();
        }

        if ($purchaseInitCommandResult->paymentTemplateCollection() instanceof PaymentTemplateCollection) {
            $paymentTemplateCollection = clone $purchaseInitCommandResult->paymentTemplateCollection();

            /** @var PaymentTemplate $firstPaymentTemplate */
            $firstPaymentTemplate = $paymentTemplateCollection->firstPaymentTemplate();
            $billerName           = $firstPaymentTemplate ? $firstPaymentTemplate->billerName() : null;

            if ($purchaseInitCommandResult->forcedBiller()) {
                $billerName = $purchaseInitCommandResult->forcedBiller();
            }

            $response['paymentTemplateInfo'] = $paymentTemplateCollection->filterByBiller($billerName);
        }

        if (!empty($purchaseInitCommandResult->nuData())) {
            $response['nuData'] = $purchaseInitCommandResult->nuData();
        }

        if (!empty($purchaseInitCommandResult->nextAction())) {
            $response['nextAction'] = $purchaseInitCommandResult->nextAction();
        }

        if (!empty($purchaseInitCommandResult->cryptoSettings())) {
            $response['cryptoSettings'] = $purchaseInitCommandResult->cryptoSettings();
        }

        $this->initResponse = $response;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->initResponse;
    }
}
