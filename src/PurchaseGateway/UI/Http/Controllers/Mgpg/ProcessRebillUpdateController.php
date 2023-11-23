<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProbillerMGPG\Exception\InvalidPaymentMethodException;
use ProbillerMGPG\Exception\InvalidPaymentTypeException;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ValidationException;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessRebillUpdateCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\RebillUpdateProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessRebillUpdateNgToMgpgService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\ProcessRebillUpdateRequest;

class ProcessRebillUpdateController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;
    /**
     * @var ProcessRebillUpdateNgToMgpgService
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
     * @var RebillUpdateProcess
     */
    private $rebillUpdateProcess;

    /**
     * ProcessPurchaseController constructor.
     * @param ProcessPurchaseDTOAssembler $dtoAssembler
     * @param ProcessRebillUpdateNgToMgpgService $ngToMgpgService
     * @param ProcessPurchaseMgpgToNgService $mgpgToNgService
     * @param MgpgClientApi $client
     * @param RebillUpdateProcess $rebillUpdateProcess
     */
    public function __construct(
        ProcessPurchaseDTOAssembler $dtoAssembler,
        ProcessRebillUpdateNgToMgpgService $ngToMgpgService,
        ProcessPurchaseMgpgToNgService $mgpgToNgService,
        MgpgClientApi $client,
        RebillUpdateProcess $rebillUpdateProcess
    ) {
        $this->dtoAssembler        = $dtoAssembler;
        $this->ngToMgpgService     = $ngToMgpgService;
        $this->mgpgToNgService     = $mgpgToNgService;
        $this->mgpgClient          = $client;
        $this->rebillUpdateProcess = $rebillUpdateProcess;
    }

    /**
     * @param ProcessRebillUpdateRequest $request Input payload from NG
     * @return JsonResponse
     * @throws Exception
     */
    public function post(ProcessRebillUpdateRequest $request): JsonResponse
    {
        try {
            Log::info('MGPGProcessRebillUpdateAdaptor Beginning process purchase');

            $command     = ProcessRebillUpdateCommand::createCommandFromRequest($request);
            $mgpgRequest = $this->ngToMgpgService->translate($request);

            Log::info(
                "MGPGProcessRebillUpdateAdaptor Created MGPG Request from NG Request",
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
                Log::info("MGPGProcessRebillUpdateAdaptor Using overrides", ['overrides' => json_encode($overrides)]);
                $this->mgpgClient->setOverrides($overrides);
            }

            $mgpgResponse = $this->mgpgClient->subsequentProcess(
                $mgpgRequest,
                $command->getMgpgAuthToken(),
                $command->getCorrelationId(),
                $command->getMgpgSessionId()
            );

            Log::info("MGPGProcessRebillUpdateAdaptor Response received from MGPG", ['response' => json_encode($mgpgResponse)]);

            if ($mgpgResponse instanceof ErrorResponse) {
                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            $this->rebillUpdateProcess->create($mgpgResponse, $command);

            $dto = $this->mgpgToNgService->translate($this->rebillUpdateProcess, $this->dtoAssembler);

            $this->mgpgToNgService->queuePostback($this->rebillUpdateProcess, $dto, $command->getPostbackUrl());

            return new JsonResponse($dto);
        } catch (ErrorResponseException|ValidationException|IllegalStateTransitionException|InvalidPaymentTypeException|InvalidPaymentMethodException|InvalidUserInfoFirstName $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
