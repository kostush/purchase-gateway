<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Throwable;
use ProBillerNG\Logger\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Exception;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\CancelMgpgTranslateService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\CancelRebillRequest;

/**
 * Class CancelRebillController
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg
 */
class CancelRebillController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;

    /**
     * @var CancelMgpgTranslateService
     */
    protected $translateService;

    /**
     * CancelRebillController constructor.
     *
     * @param MgpgClientApi              $mgpgClient
     * @param CancelMgpgTranslateService $translateService
     */
    public function __construct(MgpgClientApi $mgpgClient, CancelMgpgTranslateService $translateService)
    {
        $this->mgpgClient       = $mgpgClient;
        $this->translateService = $translateService;
    }

    /**
     * @param CancelRebillRequest $request
     *
     * @return JsonResponse
     * @throws LoggerException
     */
    public function post(CancelRebillRequest $request): JsonResponse
    {
        try {
            Log::info('MGPGAdaptor Beginning cancel-rebill operation');

            $mgpgRequest = $this->translateService->translateCancelTo($request);

            Log::info(
                "MGPGAdaptor created MGPG CancelRebillRequest from NG Request",
                [
                    'ngRequestPayload'   => json_encode($request->toArray()),
                    'mgpgRequestPayload' => json_encode($mgpgRequest->toArray())
                ]
            );

            $mgpgResponse = $this->mgpgClient->cancel($mgpgRequest, Log::getCorrelationId());

            Log::info(
                "MGPGAdaptor received response from MGPG for cancel-rebill operation",
                ['response' => json_encode($mgpgResponse)]
            );

            if ($mgpgResponse instanceof ErrorResponse) {
                Log::error("MGPGAdaptor Error", ["message" => $mgpgResponse->getErrorMessage()]);

                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            $translatedResponse = $this->translateService->translateCancelFrom($mgpgResponse);

            Log::info(
                "MGPGAdaptor translated response for cancel-rebill operation",
                ['response' => json_encode($translatedResponse)]
            );

            return response()->json($translatedResponse, Response::HTTP_OK);
        }catch (ErrorResponseException | Exception $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            Log::logException($e);

            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}