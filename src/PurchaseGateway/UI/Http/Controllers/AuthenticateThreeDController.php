<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\Authenticate\AuthenticateThreeDQuery;
use ProBillerNG\PurchaseGateway\Application\Services\Authenticate\AuthenticateThreeDQueryHandler;

class AuthenticateThreeDController extends Controller
{
    /**
     * @var AuthenticateThreeDQueryHandler
     */
    private $handler;

    /**
     * AuthenticateThreeDController constructor.
     *
     * @param AuthenticateThreeDQueryHandler $handler Handler
     */
    public function __construct(AuthenticateThreeDQueryHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * @param Request $request Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticatePurchase(Request $request)
    {
        $decodedSessionId = $request->get('sessionId');

        try {
            $query  = new AuthenticateThreeDQuery($decodedSessionId);
            $result = $this->handler->execute($query);
        } catch (MissingRedirectUrlException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_BAD_REQUEST);
        } catch (SessionAlreadyProcessedException $ex) {
            return response(
                $this->errorRedirectToClient(
                    $this->buildResponse($ex),
                    $ex->returnUrl()
                ),
                Response::HTTP_BAD_REQUEST
            );
        } catch (SessionNotFoundException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_NOT_FOUND);
        } catch (\Exception $ex) {
            return response($this->serverError(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response($this->successRedirectToBank($result->toArray()), Response::HTTP_OK);
    }

    /**
     * @param array $result Result
     * @return View
     */
    private function successRedirectToBank(array $result): View
    {
        $routeParams = [
            'jwt' => $result['jwt']
        ];

        $params = [
            'authUrl'   => $result['acs'],
            'pareq'     => $result['pareq'],
            'returnUrl' => route('threed.complete', $routeParams)
        ];
        return view('threeD.authenticate', $params);
    }
}
