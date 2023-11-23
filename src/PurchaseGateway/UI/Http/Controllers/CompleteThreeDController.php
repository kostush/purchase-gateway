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
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingParesAndMdException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToCompleteThreeDTransactionException;
use Throwable;

class CompleteThreeDController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    private $handler;

    /**
     * CompleteThreeDController constructor.
     *
     * @param CompleteThreeDCommandHandler $handler Handler
     * @param TransactionalSession         $session Session
     */
    public function __construct(CompleteThreeDCommandHandler $handler, TransactionalSession $session)
    {
        $this->handler = new TransactionalCommandHandler($handler, $session);
    }

    /**
     * @param Request $request Request
     * @return JsonResponse  "The session is not a valid uuid
     * @throws Exception
     * @throws Throwable
     */
    public function completePurchase(Request $request)
    {
        $pares = $request->input('PaRes', '');
        // rocketgate biller transaction id.
        $md               = $request->input('MD', '');
        $decodedSessionId = $request->get('sessionId');

        Log::info('Beginning the process of complete purchase');

        try {
            $command = new CompleteThreeDCommand($decodedSessionId, $pares, (string) $md);
            $result  = $this->handler->execute($command);
            $result  = $result->jsonSerialize();
        } catch (MissingRedirectUrlException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_BAD_REQUEST);
        } catch (MissingParesAndMdException | SessionAlreadyProcessedException $ex) {
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
}
