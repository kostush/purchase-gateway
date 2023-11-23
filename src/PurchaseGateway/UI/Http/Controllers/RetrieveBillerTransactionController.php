<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveBillerTransactionQueryHandler;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveItemQuery;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\RetrieveTransactionDataException;

class RetrieveBillerTransactionController extends Controller
{
    /**
     * @var RetrieveBillerTransactionQueryHandler
     */
    protected $handler;

    /**
     * RetrieveBillerTransactionController constructor.
     * @param RetrieveBillerTransactionQueryHandler $handler RetrieveBillerTransactionQueryHandler
     */
    public function __construct(RetrieveBillerTransactionQueryHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param string  $sessionId Session Id
     * @param Request $request   Request object
     * @return false|\Illuminate\Http\JsonResponse|string
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function retrieve(string $sessionId, Request $request)
    {
        // if the sessionId provided on the request sent is invalid:
        // - a new session uuid will be generated
        // - the logs with the invalid session id provided and the new one just generated will be added
        $this->validateAndLogSessionIdDifferences($sessionId, $request->attributes->get('sessionId'));

        $itemId = (string) $request->input('itemId');

        Log::info('Begin biller transaction data retrieval process', ['itemId' => $itemId]);

        try {
            $query  = new RetrieveItemQuery($itemId, $sessionId);
            $result = $this->handler->execute($query);

            return response()->json($result, Response::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->badRequest($e);
        } catch (NotFoundException | RetrieveTransactionDataException $e) {
            return $this->notFound($e);
        } catch (\Throwable $e) {
            return $this->internalServerError($e);
        }
    }
}
