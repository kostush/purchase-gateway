<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Throwable;
use ProBillerNG\Logger\Log;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Exception\GuzzleException;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\RefundRequest;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\RefundMgpgTranslateService;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;

/**
 * Class RefundController
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg
 */
class RefundController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;

    /**
     * @var RefundMgpgTranslateService
     */
    protected $translateService;

    /**
     * RefundController constructor.
     *
     * @param MgpgClientApi              $mgpgClient
     * @param RefundMgpgTranslateService $translateService
     */
    public function __construct(MgpgClientApi $mgpgClient, RefundMgpgTranslateService $translateService)
    {
        $this->mgpgClient       = $mgpgClient;
        $this->translateService = $translateService;
    }

    /**
     * @param RefundRequest $request
     *
     * @return JsonResponse
     * @throws \Exception|GuzzleException
     */
    public function post(RefundRequest $request): JsonResponse
    {
        try {
            Log::info('MGPGRefundAdaptor Beginning refund operation');

            $mgpgRequest = $this->translateService->translateTo($request);

            Log::info(
                'MGPGRefundAdaptor created MGPG RefundRequest from NG Request',
                [
                    'ngRequestPayload'   => json_encode($request->toArray()),
                    'mgpgRequestPayload' => json_encode($mgpgRequest->toArray())
                ]
            );

            $correlationId = $request->headers->get('X-CORRELATION-ID');
            $mgpgResponse = $this->mgpgClient->refund($mgpgRequest, $correlationId);

            Log::info(
                'MGPGRefundAdaptor received response from MGPG for refund operation',
                ['response' => json_encode($mgpgResponse)]
            );

            if ($mgpgResponse instanceof ErrorResponse) {
                Log::error('MGPGRefundAdaptor Error', ['message' => $mgpgResponse->getErrorMessage()]);

                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            /** RefundResponse $mgpgResponse */
            return response()->json($mgpgResponse, Response::HTTP_OK);
        } catch (ErrorResponseException | Exception $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            Log::logException($e);

            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
