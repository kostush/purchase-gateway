<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException as TransactionAlreadyProcessed;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect\ThirdPartyRedirectQuery;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyRedirect\ThirdPartyRedirectQueryHandler;

class ThirdPartyRedirectController extends Controller
{
    /**
     * @var ThirdPartyRedirectQueryHandler
     */
    private $handler;

    /**
     * ThirdPartyRedirectController constructor.
     *
     * @param ThirdPartyRedirectQueryHandler $handler Handler
     */
    public function __construct(ThirdPartyRedirectQueryHandler $handler)
    {
        $this->handler = $handler;
    }
    /**
     * @param Request $request Request
     * @return JsonResponse
     */
    public function redirectPurchase(Request $request)
    {
        $decodedSessionId = $request->get('sessionId');

        try {
            $query  = new ThirdPartyRedirectQuery($decodedSessionId);
            $result = $this->handler->execute($query);
        } catch (BillerMappingException | MissingRedirectUrlException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_BAD_REQUEST);
        } catch (SessionAlreadyProcessedException $ex) {
            return response(
                $this->errorRedirectToClient(
                    $this->buildResponse($ex),
                    $ex->returnUrl()
                ),
                Response::HTTP_BAD_REQUEST
            );
        } catch (TransactionAlreadyProcessed $ex) {
            return response(
                $this->errorRedirectToClient(
                    $this->buildResponse($ex),
                    $ex->returnUrl()
                ),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (SessionNotFoundException $ex) {
            return response($this->errorRequest($ex), Response::HTTP_NOT_FOUND);
        } catch (\Exception $ex) {
            return response($this->serverError(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response($this->successUserRedirect($result->jsonSerialize()), Response::HTTP_OK);
    }

    /**
     * @param array $result Result
     * @return View
     */
    private function successUserRedirect(array $result): View
    {
        if (isset($result['success'])) {
            return $this->redirectToClient($result);
        }

        $params = [
            'redirectUrl' => $result['redirectUrl']
        ];
        return view('thirdParty.redirect', $params);
    }

    /**
     * @param array $response Response
     * @return View
     */
    private function redirectToClient(array $response): View
    {
        $redirectUrl = $response['redirectUrl'];

        unset($response['redirectUrl']);

        $params = [
            'clientUrl' => $redirectUrl,
            'response'  => $response
        ];

        return view('thirdParty.redirectToClient', $params);
    }
}
