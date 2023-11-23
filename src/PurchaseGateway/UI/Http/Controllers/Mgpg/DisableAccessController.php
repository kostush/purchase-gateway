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
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\DisableAccessRequest;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\DisableMgpgTranslateService;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;

class DisableAccessController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;

    /**
     * @var DisableMgpgTranslateService
     */
    protected $translateService;

    /**
     * DisableAccessController constructor.
     *
     * @param MgpgClientApi               $mgpgClient
     * @param DisableMgpgTranslateService $translateService
     */
    public function __construct(MgpgClientApi $mgpgClient, DisableMgpgTranslateService $translateService)
    {
        $this->mgpgClient       = $mgpgClient;
        $this->translateService = $translateService;
    }

    /**
     * @param DisableAccessRequest $request
     *
     * @return JsonResponse
     * @throws LoggerException
     */
    public function post(DisableAccessRequest $request): JsonResponse
    {
        try {
            Log::info('MGPGAdaptor Beginning disable-access operation');

            $mgpgRequest = $this->translateService->translateDisableTo($request);

            Log::info(
                "MGPGAdaptor created MGPG DisableAccessRequest from NG Request",
                [
                    'ngRequestPayload'   => json_encode($request->toArray()),
                    'mgpgRequestPayload' => json_encode($mgpgRequest->toArray())
                ]
            );

            $mgpgResponse = $this->mgpgClient->disable($mgpgRequest, Log::getCorrelationId());

            Log::info(
                "MGPGAdaptor received response from MGPG for disable-access operation",
                ['response' => json_encode($mgpgResponse)]
            );

            if ($mgpgResponse instanceof ErrorResponse) {
                Log::error("MGPGAdaptor Error", ["message" => $mgpgResponse->getErrorMessage()]);

                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            $translatedResponse = $this->translateService->translateDisableFrom($mgpgResponse);

            Log::info(
                "MGPGAdaptor translated response for disable-access operation",
                ['response' => json_encode($translatedResponse)]
            );

            return response()->json($translatedResponse, Response::HTTP_OK);
        } catch (ErrorResponseException | Exception $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            Log::logException($e);

            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}