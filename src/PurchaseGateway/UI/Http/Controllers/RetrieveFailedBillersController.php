<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers\RetrieveFailedBillersQuery;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveFailedBillers\RetrieveFailedBillersQueryHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ProBillerNG\Logger\Log;

class RetrieveFailedBillersController extends Controller
{
    /**
     * @var RetrieveFailedBillersQueryHandler
     */
    private $handler;

    /**
     * RetrieveFailedBillersController constructor.
     * @param RetrieveFailedBillersQueryHandler $handler The query handler
     */
    public function __construct(RetrieveFailedBillersQueryHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param \Illuminate\Http\Request $request Request
     * @return HttpResponse
     * @throws \Exception
     */
    public function retrieve(\Illuminate\Http\Request $request)
    {
        // if the sessionId provided on the request sent is invalid:
        // - a new session uuid will be generated
        // - the logs with the invalid session id provided and the new one just generated will be added
        $this->validateAndLogSessionIdDifferences(
            $request->route('sessionId'),
            $request->attributes->get('sessionId')
        );

        try {
            Log::info(
                'Begin failed billers retrieval process',
                ['sessionId' => $request->get('sessionId')]
            );

            $query  = new RetrieveFailedBillersQuery($request->get('sessionId'));
            $result = $this->handler->execute($query);

            return response()->json($result, Response::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->badRequest($e);
        } catch (NotFoundException $e) {
            return $this->notFound($e);
        } catch (\Throwable $e) {
            return $this->internalServerError($e);
        }
    }
}
