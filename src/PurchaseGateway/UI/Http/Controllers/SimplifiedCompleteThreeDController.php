<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingMandatoryQueryParamsForCompleteException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToCompleteThreeDTransactionException;
use Throwable;

class SimplifiedCompleteThreeDController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    private $handler;

    /**
     * @var InMemoryRepository
     */
    private $redisRepository;

    /**
     * SimplifiedCompleteThreeDController constructor.
     *
     * @param SimplifiedCompleteThreeDCommandHandler $handler         Handler
     * @param TransactionalSession                   $session         Session
     * @param InMemoryRepository                     $redisRepository Redis repository
     */
    public function __construct(
        SimplifiedCompleteThreeDCommandHandler $handler,
        TransactionalSession $session,
        InMemoryRepository $redisRepository
    ) {
        $this->handler         = new TransactionalCommandHandler($handler, $session);
        $this->redisRepository = $redisRepository;
    }

    /**
     * @param Request $request Request
     * @return JsonResponse  "The session is not a valid uuid
     * @throws Exception
     * @throws Throwable
     */
    public function completePurchase(Request $request)
    {
        $decodedSessionId = $request->get('sessionId');
        $queryString      = $request->query->all();

        Log::info('Beginning the process of the simplified complete purchase');

        try {
            $this->handleConcurrentCalls($decodedSessionId);

            $command = new SimplifiedCompleteThreeDCommand($decodedSessionId, $queryString);
            $result  = $this->handler->execute($command);
            $result  = $result->jsonSerialize();
        } catch (MissingMandatoryQueryParamsForCompleteException | SessionAlreadyProcessedException $ex) {
            return response(
                $this->errorRedirectToClient(
                    $this->buildResponse($ex),
                    $ex->returnUrl()
                ),
                Response::HTTP_BAD_REQUEST
            );
        } catch (SessionNotFoundException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_NOT_FOUND);
        } catch (UnableToCompleteThreeDTransactionException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_FAILED_DEPENDENCY);
        } catch (Exception $ex) {
            return response($this->serverError(), Response::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            // delete the session id key from redis - needed for concurrent calls
            $this->redisRepository->deleteSessionId($decodedSessionId);

            // delete the purchase status and gateway submit number from redis - needed for multiple submits
            if ($this->redisRepository->retrievePurchaseStatus($decodedSessionId) === Processed::name()) {
                $this->redisRepository->deletePurchaseStatus($decodedSessionId);
                $this->redisRepository->deleteGatewaySubmitNumber($decodedSessionId);
            }
        }

        return response($this->successRedirectToClient($result), Response::HTTP_OK);
    }

    /**
     * @param array $response Response
     * @return View
     */
    private function successRedirectToClient(array $response): View
    {
        $redirectUrl = $response['redirectUrl'];

        unset($response['redirectUrl']);

        $params = [
            'clientUrl' => $redirectUrl,
            'response'  => $response
        ];

        return view('threeD.complete', $params);
    }

    /**
     * Session id acts as a flag in redis. By using the 'setnx' feature in redis, we know
     * whether a concurrent call is initiated. If a concurrent call is performed, we have
     * to make sure the initial call is finished before attempting another one. This is what
     * the sleep is needed for.
     *
     * @param string $sessionId Session id
     * @return void
     */
    private function handleConcurrentCalls(string $sessionId): void
    {
        $iteration = 1;

        do {
            $response = $this->redisRepository->storeSessionId($sessionId);

            // the key was not set, meaning there is an ongoing
            // process already started before this one
            if ($response === false) {
                sleep(1);
            }
        } while ($response === false && $iteration++ < 30);
    }
}
