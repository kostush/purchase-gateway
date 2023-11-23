<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Lcobucci\JWT\Parser;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProbillerMGPG\Exception\InvalidPaymentMethodException;
use ProbillerMGPG\Exception\InvalidPaymentTypeException;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ValidationException;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseNgToMgpgService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\ProcessPurchaseRequest;
use Ramsey\Uuid\Uuid;

class ProcessPurchaseController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;
    /**
     * @var ProcessPurchaseNgToMgpgService
     */
    protected $ngToMgpgService;

    /**
     * @var ProcessPurchaseMgpgToNgService
     */
    protected $mgpgToNgService;

    /**
     * @var ProcessPurchaseDTOAssembler
     */
    protected $dtoAssembler;

    /**
     * ProcessPurchaseController constructor.
     * @param ProcessPurchaseDTOAssembler    $dtoAssembler
     * @param ProcessPurchaseNgToMgpgService $ngToMgpgService
     * @param ProcessPurchaseMgpgToNgService $mgpgToNgService
     * @param MgpgClientApi                  $client
     */
    public function __construct(
        ProcessPurchaseDTOAssembler $dtoAssembler,
        ProcessPurchaseNgToMgpgService $ngToMgpgService,
        ProcessPurchaseMgpgToNgService $mgpgToNgService,
        MgpgClientApi $client
    ) {
        $this->dtoAssembler    = $dtoAssembler;
        $this->ngToMgpgService = $ngToMgpgService;
        $this->mgpgToNgService = $mgpgToNgService;
        $this->mgpgClient      = $client;
    }

    /**
     * @param ProcessPurchaseRequest $request Input payload from NG
     * @return JsonResponse
     * @throws Exception
     */
    public function post(ProcessPurchaseRequest $request): JsonResponse
    {
        try {
            Log::info('MGPGProcessPurchaseAdaptor Beginning process purchase');

            $command     = $this->createCommand($request);
            $mgpgRequest = $this->ngToMgpgService->translate($request, $command->getSelectedChargeIds());

            Log::info(
                'MGPGProcessPurchaseAdaptor Created MGPG Request from NG Request',
                ['payload' => $mgpgRequest->toArray()],
                [
                    'payload.payment.paymentInformation.ccNumber',
                    'payload.payment.paymentInformation.cvv',
                    'payload.payment.checkInformation.routingNumber',
                    'payload.payment.checkInformation.accountNumber',
                    'payload.payment.checkInformation.socialSecurityLast4',
                    'payload.payment.checkInformation.savingAccount',
                    'payload.memberProfile.password',
                ]
            );

            if ($overrides = $request->overrides()) {
                Log::info('MGPGProcessPurchaseAdaptor Using overrides', ['overrides' => json_encode($overrides)]);
                $this->mgpgClient->setOverrides($overrides);
            }

            $mgpgResponse = $this->mgpgClient->purchaseProcess(
                $mgpgRequest,
                $command->getMgpgAuthToken(),
                $command->getCorrelationId(),
                $command->getMgpgSessionId()
            );

            Log::info('MGPGProcessPurchaseAdaptor Response received from MGPG', ['response' => json_encode($mgpgResponse)]);

            if ($mgpgResponse instanceof ErrorResponse) {
                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            $purchaseProcess = new PurchaseProcess($mgpgResponse, $command);

            $dto = $this->mgpgToNgService->translate($purchaseProcess, $this->dtoAssembler);

            return new JsonResponse($dto);
        } catch (ErrorResponseException|ValidationException|IllegalStateTransitionException|InvalidPaymentTypeException|InvalidPaymentMethodException|InvalidUserInfoFirstName $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function createCommand(ProcessPurchaseRequest $request)
    {
        $token                = (new Parser())->parse($request->bearerToken());
        $fallbackPostbackUrl  = $request->attributes->get('site')->postbackUrl();
        $crossSaleChargeIdMap = json_decode(
            $token->getClaim('X-Cross-Sale-Charge-Id-Map'), true
        );

        $selectedChargeIds = $this->ngToMgpgService->selectedChargeIds($request, $crossSaleChargeIdMap);

        return new ProcessPurchaseCommand(
            $request->attributes->get('sessionId'),
            $request->headers->get('X-CORRELATION-ID'),
            $token->getClaim('X-Mgpg-Session-Id'),
            (int) $token->getClaim('X-Public-Key-Id'),
            $token->getClaim('X-Postback-Url') ?? $fallbackPostbackUrl,
            $token->getClaim('X-Return-Url') ?? '',
            $token->getClaim('X-Mgpg-Auth-Token'),
            $selectedChargeIds
        );
    }
}
