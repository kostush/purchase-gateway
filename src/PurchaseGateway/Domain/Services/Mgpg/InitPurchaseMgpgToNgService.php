<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProbillerMGPG\Purchase\Init\PurchaseInitResponse;
use ProbillerMGPG\Purchase\Init\Response\PaymentTemplateInfo;
use ProbillerMGPG\Response as MgpgResponse;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\BlankPaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BasePaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\UnknownBiller;

class InitPurchaseMgpgToNgService
{
    /**
     * @var NgResponseService
     */
    protected $ngResponseService;
    /**
     * @var PurchaseInitCommandResult
     */
    private $purchaseInitCommandResult;
    /**
     * @var MgpgResponseService
     */
    private $mgpgResponseService;
    /**
     * @var PurchaseInitDTOAssembler
     */
    private $dtoAssembler;

    /**
     * InitPurchaseNgToMgpgService constructor.
     * @param PurchaseInitCommandResult $purchaseInitCommandResult Used to create NG response
     * @param NgResponseService         $ngResponseService
     * @param MgpgResponseService       $mgpgResponseService       Utility to inspect the MGPG response
     * @param PurchaseInitDTOAssembler  $dtoAssembler              Provides final response structure
     */
    public function __construct(
        PurchaseInitCommandResult $purchaseInitCommandResult,
        NgResponseService $ngResponseService,
        MgpgResponseService $mgpgResponseService,
        PurchaseInitDTOAssembler $dtoAssembler
    ) {
        $this->purchaseInitCommandResult = $purchaseInitCommandResult;
        $this->ngResponseService         = $ngResponseService;
        $this->mgpgResponseService       = $mgpgResponseService;
        $this->dtoAssembler              = $dtoAssembler;
    }

    /**
     * @param PurchaseInitResponse        $mgpgResponse Response received from MGPG
     * @param InitPurchaseNgToMgpgService $ngToMgpgService
     * @return JsonResponse
     */
    public function translate(
        PurchaseInitResponse $mgpgResponse,
        InitPurchaseNgToMgpgService $ngToMgpgService
    ): JsonResponse {
        $initRequest = $ngToMgpgService->getInitRequest();

        $this->purchaseInitCommandResult->addSessionId($initRequest->attributes->get('sessionId'));
        $this->purchaseInitCommandResult->addMgpgSessionId($mgpgResponse->sessionId);
        $this->purchaseInitCommandResult->addCorrelationId($mgpgResponse->correlationId);
        $this->purchaseInitCommandResult->addSubscriptionId($ngToMgpgService->getSubscriptionId());
        $this->purchaseInitCommandResult->addMemberId($ngToMgpgService->getMemberId());

        if ($mgpgResponse->nuData) {
            $this->purchaseInitCommandResult->addNuData(
                NuDataSettings::create(
                    $mgpgResponse->nuData->clientId,
                    $mgpgResponse->nuData->url,
                    $mgpgResponse->nuData->enabled
                )
            );
        }

        $this->purchaseInitCommandResult->addFraudAdvice(
            $this->mgpgResponseService->translateFraudAdviceInitStep($mgpgResponse)
        );

        $fraudRecommendation = $this->translateFraudRecommandation($mgpgResponse);

        $this->purchaseInitCommandResult->addFraudRecommendation($fraudRecommendation);

        $this->purchaseInitCommandResult->addFraudRecommendationCollection(
            new FraudRecommendationCollection([$fraudRecommendation])
        );

        if ($templates = $this->translatePaymentTemplateInfo($mgpgResponse)) {
            $this->purchaseInitCommandResult->addPaymentTemplateCollection($templates);
        }

        $this->purchaseInitCommandResult->setRawNextAction(
            $this->translateNextAction($mgpgResponse)
        );

        $this->purchaseInitCommandResult->addCryptoSettings($mgpgResponse->cryptoSettings);

        $response = $this->dtoAssembler->assemble($this->purchaseInitCommandResult);

        return new JsonResponse(
            $response,
            Response::HTTP_OK,
            // Setting temporary headers to form the Auth-Token as these fields are required for process and we don't have
            // a session to pull from. See ResponseToken middleware.
            [
                'X-Mgpg-Auth-Token'          => $mgpgResponse->jwtToken,
                'X-Mgpg-Session-Id'          => $mgpgResponse->sessionId,
                'X-Session-Id'               => $initRequest->attributes->get('sessionId'),
                'X-Public-Key-Id'            => $initRequest->attributes->get('publicKeyId'),
                'X-Return-Url'               => $initRequest->getRedirectUrl(),
                'X-Postback-Url'             => $initRequest->getPostbackUrl(),
                'X-Cross-Sale-Charge-Id-Map' => $ngToMgpgService->getCrossSaleChargeIdMap()
            ]
        );
    }

    /**
     * @param MgpgResponse $mgpgResponse Response received from MGPG
     * @return FraudRecommendation
     */
    protected function translateFraudRecommandation(MgpgResponse $mgpgResponse): FraudRecommendation
    {
        if ($this->mgpgResponseService->blockedDueToFraudAdvice($mgpgResponse->nextAction)) {
            return FraudRecommendation::create(
                (int) $mgpgResponse->nextAction->reasonDetails->code,
                $mgpgResponse->nextAction->reasonDetails->severity,
                $mgpgResponse->nextAction->reasonDetails->message
            );
        }

        if ($this->mgpgResponseService->hasCaptcha($mgpgResponse->nextAction)) {
            // Taken verbatim from NG validateCaptcha response
            return FraudRecommendation::create(
                FraudRecommendation::CAPTCHA,
                FraudRecommendation::BLOCK,
                FraudIntegrationMapper::CAPTCHA_REQUIRED
            );
        }

        return FraudRecommendation::createDefaultAdvice();
    }

    /**
     * @param MgpgResponse $mgpgResponse Response received from MGPG
     * @return PaymentTemplateCollection|null
     */
    protected function translatePaymentTemplateInfo(MgpgResponse $mgpgResponse): ?PaymentTemplateCollection
    {
        if (empty($mgpgResponse->paymentTemplateInfo)) {
            return null;
        }

        $templates = null;
        foreach ($mgpgResponse->paymentTemplateInfo as $template) {
            $templates[] = $this->createTemplate($template, $mgpgResponse->isPaymentTemplateValidationEnabled);
        }

        return new PaymentTemplateCollection($templates);
    }

    /**
     * @param PaymentTemplateInfo $mgpgTemplate
     * @param bool                $isPaymentTemplateValidationEnabled
     * @return BlankPaymentTemplate|PaymentTemplate
     */
    protected function createTemplate(
        PaymentTemplateInfo $mgpgTemplate,
        bool $isPaymentTemplateValidationEnabled) : BasePaymentTemplate
    {
        if ($this->isVoidTemplate($mgpgTemplate)) {
            return BlankPaymentTemplate::create($mgpgTemplate->templateId);
        }

        $mgpgTemplate = PaymentTemplate::create(
            $mgpgTemplate->templateId,
            (string) $mgpgTemplate->userFriendlyIdentifier->first6 ?? null,
            null,
            (string) $mgpgTemplate->userFriendlyIdentifier->expirationYear ?? null,
            (string) $mgpgTemplate->userFriendlyIdentifier->expirationMonth ?? null,
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            UnknownBiller::BILLER_NAME,
            [],
            (string) $mgpgTemplate->userFriendlyIdentifier->label ?? null
        );

        if ($isPaymentTemplateValidationEnabled == false) {
            $mgpgTemplate->setIsSafe(true);
        }

        return $mgpgTemplate;
    }

    /**
     * @param PaymentTemplateInfo $paymentTemplateInfo
     * @return bool If the payment template information suggests no validation fields
     */
    protected function isVoidTemplate(PaymentTemplateInfo $paymentTemplateInfo): bool
    {
        if (empty($paymentTemplateInfo->validationParameters) && $paymentTemplateInfo->userFriendlyIdentifier == null) {
            return true;
        }
        return false;
    }

    /**
     * @param MgpgResponse $mgpgResponse Response received from MGPG
     * @return array
     */
    protected function translateNextAction(MgpgResponse $mgpgResponse): array
    {
        $nextAction = [];
        switch ($mgpgResponse->nextAction->type) {
            case "finishProcess":
                $nextAction['type'] = 'finishProcess';

                if ($this->mgpgResponseService->cascadeBillersExhausted($mgpgResponse->nextAction)) {
                    $nextAction['reason'] = $mgpgResponse->nextAction->reason;
                }

                return $nextAction;
            case "validateCaptcha":
                $nextAction['type'] = 'renderGateway';

                return $nextAction;
            case "redirectToUrl":
                $nextAction['type']              = 'redirectToUrl';
                $nextAction['thirdParty']['url'] = $mgpgResponse->nextAction->thirdParty->url;

                return $nextAction;
            default:
                $nextAction['type'] = $mgpgResponse->nextAction->type;

                return $nextAction;
        }
    }
}
