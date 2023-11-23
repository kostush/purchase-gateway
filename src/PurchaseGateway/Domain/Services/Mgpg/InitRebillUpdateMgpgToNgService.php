<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProbillerMGPG\SubsequentOperations\Common\NextAction;
use ProbillerMGPG\SubsequentOperations\Init\InitResponse;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgSubsequentOperationResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;

class InitRebillUpdateMgpgToNgService
{
    const FINISH_PROCESS = "finishProcess";

    const VALIDATE_CAPTCHA = "validateCaptcha";

    const REDIRECT_URL = "redirectToUrl";

    /**
     * @var PurchaseInitCommandResult
     */
    private $purchaseInitCommandResult;

    /**
     * @var PurchaseInitDTOAssembler
     */
    private $dtoAssembler;

    /**
     * @var NgResponseService
     */
    private $ngResponseService;

    /**
     * @var MgpgSubsequentOperationResponseService
     */
    private $mgpgResponseService;

    /**
     * InitPurchaseNgToMgpgService constructor.
     * @param PurchaseInitCommandResult              $purchaseInitCommandResult Used to create NG response
     * @param NgResponseService                      $ngResponseService
     * @param MgpgSubsequentOperationResponseService $mgpgResponseService       Utility to inspect the MGPG response
     * @param PurchaseInitDTOAssembler               $dtoAssembler              Provides final response structure
     */
    public function __construct(
        PurchaseInitCommandResult $purchaseInitCommandResult,
        NgResponseService $ngResponseService,
        MgpgSubsequentOperationResponseService $mgpgResponseService,
        PurchaseInitDTOAssembler $dtoAssembler
    ) {
        $this->purchaseInitCommandResult = $purchaseInitCommandResult;
        $this->dtoAssembler              = $dtoAssembler;
        $this->ngResponseService         = $ngResponseService;
        $this->mgpgResponseService       = $mgpgResponseService;
    }

    /**
     * @param InitResponse                    $initResponse Response received from MGPG
     * @param InitRebillUpdateNgToMgpgService $ngToMgpgService
     * @return JsonResponse
     * @throws Exception
     */
    public function translate(
        InitResponse $initResponse,
        InitRebillUpdateNgToMgpgService $ngToMgpgService
    ): JsonResponse {
        $initRequest = $ngToMgpgService->getInitRequest();

        $this->purchaseInitCommandResult->addSessionId($initRequest->attributes->get('sessionId'));
        $this->purchaseInitCommandResult->addMgpgSessionId($initResponse->sessionId);
        $this->purchaseInitCommandResult->addCorrelationId($initResponse->correlationId);
        $this->purchaseInitCommandResult->addFraudAdvice($this->mgpgResponseService->translateFraudAdviceInitStepToNg($initResponse));

        $fraudRecommendation = $this->translateFraudRecommendation($initResponse->nextAction);
        $this->purchaseInitCommandResult->addFraudRecommendation($fraudRecommendation);
        $this->purchaseInitCommandResult->addFraudRecommendationCollection(
            new FraudRecommendationCollection([$fraudRecommendation])
        );

        $this->purchaseInitCommandResult->addPaymentTemplateCollection(
            PaymentTemplateCollection::createFromRebillUpdateResponse(
                $initResponse->paymentTemplateInfo,
                $initResponse->isPaymentTemplateValidationEnabled
            )
        );

        $this->purchaseInitCommandResult->setRawNextAction(
            $this->translateNextAction($initResponse->nextAction)
        );

        $response = $this->dtoAssembler->assemble($this->purchaseInitCommandResult);

        return new JsonResponse(
            $response,
            Response::HTTP_OK,
            // Setting temporary headers to form the Auth-Token as these fields are required for process and we don't have
            // a session to pull from. See ResponseToken middleware.
            [
                'X-Mgpg-Auth-Token' => $initResponse->jwtToken,
                'X-Mgpg-Session-Id' => $initResponse->sessionId,
                'X-Session-Id'      => $initRequest->attributes->get('sessionId'),
                'X-Public-Key-Id'   => $initRequest->attributes->get('publicKeyId'),
                'X-Return-Url'      => $initRequest->getRedirectUrl(),
                'X-Postback-Url'    => $initRequest->getPostbackUrl()
            ]
        );
    }

    /**
     * @param NextAction $nextAction
     * @return FraudRecommendation
     * @throws Exception
     */
    protected function translateFraudRecommendation(NextAction $nextAction): FraudRecommendation
    {
        if ($this->mgpgResponseService->blockedDueToFraudAdvice($nextAction)) {
            Log::info(
                'TranslatingFraudRecommendation Blocked due to fraud.',
                ['nextAction' => $nextAction]
            );
            return FraudRecommendation::create(
                (int) $nextAction->reasonDetails->code,
                $nextAction->reasonDetails->severity,
                $nextAction->reasonDetails->message
            );
        }

        if ($this->mgpgResponseService->hasCaptcha($nextAction)) {
            Log::info(
                'TranslatingFraudRecommendation Response with fraud captcha.',
                ['nextAction' => $nextAction]
            );
            return FraudRecommendation::create(
                FraudRecommendation::CAPTCHA,
                FraudRecommendation::BLOCK,
                FraudIntegrationMapper::CAPTCHA_REQUIRED
            );
        }
        Log::info('TranslatingFraudRecommendation Creating Default advice.');
        return FraudRecommendation::createDefaultAdvice();
    }

    /**
     * @param NextAction $nextAction
     * @return array
     */
    protected function translateNextAction(
        NextAction $nextAction
    ): array
    {
        $nextActionArray = [];

        switch ($nextAction->type) {
            case self::FINISH_PROCESS:
                $nextActionArray['type'] = 'finishProcess';

                if ($this->mgpgResponseService->cascadeBillersExhausted($nextAction)) {
                    $nextActionArray['reason'] = $nextAction->reason;
                }

                return $nextActionArray;
            case self::VALIDATE_CAPTCHA:
                $nextActionArray['type'] = 'renderGateway';

                return $nextActionArray;
            case self::REDIRECT_URL:
                $nextActionArray['type']              = 'redirectToUrl';
                $nextActionArray['thirdParty']['url'] = $nextAction->thirdParty->url;

                return $nextActionArray;
            default:
                $nextActionArray['type'] = $nextAction->type;
                return $nextActionArray;
        }
    }
}
