<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use ProBillerNG\Logger\Log;
use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent\RetrieveIntegrationEventQuery;
use ProBillerNG\PurchaseGateway\Application\Services\IntegrationEvent\RetrieveIntegrationEventQueryHandler;
use Throwable;

class RetrieveIntegrationEventsController extends Controller
{
    /**
     * @var RetrieveIntegrationEventQueryHandler
     */
    protected $handler;

    /**
     * RetrieveIntegrationEventsController constructor
     *
     * @param RetrieveIntegrationEventQueryHandler $handler Non-transactional Command Handler
     */
    public function __construct(RetrieveIntegrationEventQueryHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param string $eventDate Event Date
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \ProBillerNG\Logger\Exception
     * @throws Throwable
     */
    public function retrieve(string $eventDate)
    {
        try {
            //Log::debug('Begin integration events retrieval process', ['event date' => $eventDate]);

            $command = new RetrieveIntegrationEventQuery(urldecode($eventDate));
            $result  = $this->handler->execute($command);

            return response($result, Response::HTTP_OK)->header('Content-type', 'application/json');
        } catch (Throwable $e) {
            Log::logException($e);
            throw $e;
        }
    }
}
