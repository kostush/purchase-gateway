<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProbillerMGPG\Exception\InvalidPaymentMethodException;
use ProbillerMGPG\Exception\InvalidPaymentTypeException;
use ProbillerMGPG\Exception\InvalidTypeException;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ValidationException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\InitRebillUpdateMgpgToNgService;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\InitRebillUpdateNgToMgpgService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use Ramsey\Uuid\Uuid;

class InitRebillUpdateController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;

    /**
     * InitPurchaseController constructor.
     * @param MgpgClientApi $client
     */
    public function __construct(MgpgClientApi $client)
    {
        $this->mgpgClient = $client;
    }

    /**
     * @param InitRebillUpdateNgToMgpgService $ngToMgpgService Converts NG Init payload to MGPG
     * @param InitRebillUpdateMgpgToNgService $mgpgToNgService Converts MGPG Init payload to NG
     *
     * @return JsonResponse
     * @throws Exception
     * @throws \AutoMapperPlus\Exception\UnregisteredMappingException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\RebillFieldRequiredException
     */
    public function post(
        InitRebillUpdateNgToMgpgService $ngToMgpgService,
        InitRebillUpdateMgpgToNgService $mgpgToNgService
    ): JsonResponse {
        try {
            Log::info('MGPGInitRebillUpdateAdaptor Beginning init rebill-update');

            $correlationId = $ngToMgpgService->getInitRequest()->headers->get('X-CORRELATION-ID');

            $mgpgRequest = $ngToMgpgService->translate($correlationId);

            Log::info('MGPGInitRebillUpdateAdaptor Created MGPG Request from NG Request', ['payload' => $mgpgRequest->toArray()]);

            if ($overrides = $ngToMgpgService->getInitRequest()->getOverrides()) {
                Log::info('MGPGInitRebillUpdateAdaptor Using overrides', ['overrides' => json_encode($overrides)]);
                $this->mgpgClient->setOverrides($overrides);
            }

            $mgpgResponse = $this->mgpgClient->subsequentInit($mgpgRequest, $correlationId);

            Log::info('MGPGInitRebillUpdateAdaptor Response received from MGPG', ['response' => json_encode($mgpgResponse)]);

            if ($mgpgResponse instanceof ErrorResponse) {
                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            return $mgpgToNgService->translate($mgpgResponse, $ngToMgpgService);

        } catch (ErrorResponseException|InvalidTypeException|InvalidUUIDException|SiteNotExistException|ValidationException|IllegalStateTransitionException|InvalidPaymentTypeException|InvalidPaymentMethodException $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
