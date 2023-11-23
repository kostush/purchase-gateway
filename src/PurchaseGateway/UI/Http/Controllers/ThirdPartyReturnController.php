<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use ProBillerNG\Base\Application\Services\TransactionalCommandHandler;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidPayloadException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn\ReturnCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyReturn\ReturnCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\FailedDependencyException;

class ThirdPartyReturnController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    protected $handler;

    /**
     * ThirdPartyReturnController constructor.
     * @param ReturnCommandHandler $handler Handler
     * @param TransactionalSession $session Session
     */
    public function __construct(ReturnCommandHandler $handler, TransactionalSession $session)
    {
        $this->handler = new TransactionalCommandHandler($handler, $session);
    }

    /**
     * @param Request $request Request
     * @return Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function performReturn(Request $request)
    {
        // This is only for rebill return for Qysso
        if (!empty($request->input('qyssoRebillReturnViewParams'))) {
            return response(
                $this->successRedirectToClient($request->input('qyssoRebillReturnViewParams')),
                Response::HTTP_OK
            );
        }

        // this parameter is decoded from the authentication jwt token
        // check sessionIdToken middleware for more information
        $decodedSessionId = $request->get('sessionId');

        try {
            $command = new ReturnCommand($request->all(), $decodedSessionId);
            $result  = $this->handler->execute($command);
            $result  = $result->jsonSerialize();
        } catch (SessionAlreadyProcessedException | InvalidPayloadException $ex) {
            return response(
                $this->errorRedirectToClient(
                    $this->buildResponse($ex),
                    $ex->returnUrl()
                ),
                Response::HTTP_BAD_REQUEST
            );
        } catch (MissingRedirectUrlException | UnknownBillerNameException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_BAD_REQUEST);
        } catch (SessionNotFoundException $e) {
            return response($this->errorRequest($e), Response::HTTP_NOT_FOUND);
        } catch (TransactionAlreadyProcessedException $e){
            return response($this->errorRequest($e), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (FailedDependencyException $e) {
            return $this->error($e, Response::HTTP_FAILED_DEPENDENCY);
        } catch (\Exception $ex) {
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
            'response'  => $response,
        ];

        return view('thirdParty.redirectToClient', $params);
    }
}
