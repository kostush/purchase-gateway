<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\PurchaseGatewayHealthHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\RetrievePurchaseGatewayHealthQuery;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\RetrievePurchaseGatewayHealthQueryHandler;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ProBillerNG\Logger\Log;

class PurchaseGatewayHealthCheckController extends Controller
{
    /** @var  RetrievePurchaseGatewayHealthQueryHandler */
    private $purchaseGatewayHealthHandler;

    /**
     * PurchaseGatewayHealthCheckController constructor.
     * @param RetrievePurchaseGatewayHealthQueryHandler $purchaseGatewayHealthHandler Handler
     */
    public function __construct(RetrievePurchaseGatewayHealthQueryHandler $purchaseGatewayHealthHandler)
    {
        $this->purchaseGatewayHealthHandler = $purchaseGatewayHealthHandler;
    }


    /**
     * @param \Illuminate\Http\Request $request Request
     * @return HttpResponse
     * @throws \Exception
     */
    public function retrieve(\Illuminate\Http\Request $request)
    {
        try {
            Log::info('Begin purchase gateway health retrieval process');

            $retrievePostbackStatus = $request->input('postbackStatus', false);

            $command = new RetrievePurchaseGatewayHealthQuery($retrievePostbackStatus);

            $result = $this->purchaseGatewayHealthHandler->execute($command);

            if ($result->toArray()[PurchaseGatewayHealthHttpDTO::STATUS] == RetrievePurchaseGatewayHealthQueryHandler::HEALTH_OK) {
                return response($result, HttpResponse::HTTP_OK);
            } else {
                return response($result, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return $this->error($e, HttpResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
