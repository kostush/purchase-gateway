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
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ValidationException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\InitPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\InitPurchaseNgToMgpgService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use Throwable;

/**
 * Class InitPurchaseController
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg
 */
class InitPurchaseController extends Controller
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
     * @param InitPurchaseNgToMgpgService $ngToMgpgService Converts NG Init payload to MGPG
     * @param InitPurchaseMgpgToNgService $mgpgToNgService Converts MGPG Init payload to NG
     * @return JsonResponse
     * @throws Exception
     */
    public function post(
        InitPurchaseNgToMgpgService $ngToMgpgService,
        InitPurchaseMgpgToNgService $mgpgToNgService
    ): JsonResponse {
        try {
            Log::info('MGPGInitPurchaseAdaptor Beginning init purchase');

            $correlationId = $ngToMgpgService->getInitRequest()->headers->get('X-CORRELATION-ID');
            $mgpgRequest = $ngToMgpgService->translate($correlationId);

            Log::info('MGPGInitPurchaseAdaptor Created MGPG Request from NG Request', ['payload' => $mgpgRequest->toArray()]);

            if ($overrides = $ngToMgpgService->getInitRequest()->getOverrides()) {
                Log::info('MGPGInitPurchaseAdaptor Using overrides', ['overrides' => json_encode($overrides)]);
                $this->mgpgClient->setOverrides($overrides);
            }

            $mgpgResponse = $this->mgpgClient->purchaseInit($mgpgRequest, $correlationId);

            Log::info('MGPGInitPurchaseAdaptor Response received from MGPG', ['response' => json_encode($mgpgResponse)]);

            if ($mgpgResponse instanceof ErrorResponse) {
                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            return $mgpgToNgService->translate($mgpgResponse, $ngToMgpgService);

        } catch (ErrorResponseException|InvalidTypeException|InvalidUUIDException|SiteNotExistException|ValidationException|IllegalStateTransitionException|InvalidPaymentTypeException|InvalidPaymentMethodException $e) {
            return $this->error($e, Response::HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            Log::logException($e);
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
