<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware\Utils\UuidValidatorTrait;
use App\Logger;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenSessionException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionTokenExpiredException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\SessionToken;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use Ramsey\Uuid\Uuid;

class SessionIdToken
{
    use UuidValidatorTrait, Logger;

    /**
     * @var PurchaseProcessHandler
     */
    protected $purchaseProcessHandler;

    /**
     * @var SessionToken
     */
    private $tokenAuthService;

    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * SessionIdToken constructor.
     * @param PurchaseProcessHandler $purchaseProcessHandler Session handler
     * @param SessionToken           $tokenAuthService       Token Auth Service.
     * @param CryptService|null      $cryptService           Crypt service
     */
    public function __construct(
        PurchaseProcessHandler $purchaseProcessHandler,
        SessionToken $tokenAuthService,
        CryptService $cryptService = null
    ) {
        $this->purchaseProcessHandler = $purchaseProcessHandler;
        $this->tokenAuthService       = $tokenAuthService;
        $this->cryptService           = $cryptService;
    }

    /**
     * @param Request  $request Request.
     * @param \Closure $next    Next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, \Closure $next)
    {
        $this->initLogger('APP_LOG_FILE', $request);
        // Get auth token from request
        $authToken = $request->route('jwt');

        // If auth token missing, reject
        if (empty($authToken)) {
            $this->logRequest($request, $next);
            throw new InvalidTokenException();
        }

        try {
            $this->tokenAuthService->decodeToken($authToken, env('APP_JWT_KEY'));
        } catch (\Exception $ex) {
            $this->logRequest($request, $next);
            throw new InvalidTokenException();
        }

        $sessionId        = $this->tokenAuthService->sessionId();
        $decodedSessionId = $this->cryptService->decrypt($sessionId);

        // This is only for rebill return for Qysso
        // Adding the params on request to that we can check in controller
        // If they are on request the redirectToClient view is displayed
        $qyssoRebillReturnViewParams = $this->qyssoRebillReturnViewParams($request->all(), $decodedSessionId);
        if (!empty($qyssoRebillReturnViewParams)) {
            $request->merge(['qyssoRebillReturnViewParams' => $qyssoRebillReturnViewParams]);
        }

        // Skipping JWT expiration check for rebill return for Qysso
        // The logger middleware is not yet initialized so therefore no exception is logged
        if (empty($qyssoRebillReturnViewParams) && $this->tokenAuthService->checkIsExpired()) {
            $this->logRequest($request, $next);
            throw new SessionTokenExpiredException();
        }

        if (!$this->tokenAuthService->isValid()) {
            throw new InvalidTokenException();
        }

        if (!Uuid::isValid($decodedSessionId)) {
            $this->logRequest($request, $next);
            throw new InvalidTokenSessionException();
        }

        $this->setSessionIdOnRoute($request, $decodedSessionId);

        Log::updateSessionId($decodedSessionId);

        return $this->logRequest($request, $next);
    }

    /**
     * @param Request $request   The request object
     * @param string  $sessionId The session uuid string
     * @return void
     */
    private function setSessionIdOnRoute(Request $request, string $sessionId): void
    {
        //set the session id on the route, so the logger class can retrieve it consistently with the other services
        $route                 = $request->route();
        $route[2]['sessionId'] = $sessionId;

        $request->setRouteResolver(
            function () use ($route) {
                return $route;
            }
        );

        //set the session id on the request attributes because for an unknown reason it can't be retrieved
        //form the request object on the controller
        $request->attributes->set('sessionId', $sessionId);
    }

    /**
     * @param Request  $request Request
     * @param \Closure $next    Next
     * @return mixed
     * @throws \ProBillerNG\Logger\Exception
     */
    private function logRequest(Request $request, \Closure $next)
    {
        if ($request->header('ignore-log') !== "1") {
            Log::logRequest(
                $request,
                [
                    'payment.ccNumber',
                    'payment.cvv',
                    'member.password',
                ]
            );
        }

        $response = $next($request);

        if ($request->header('ignore-log') !== "1" && $response) {
            Log::logResponse($response);
        }

        return $response;
    }

    /**
     * @param array  $payload   Payload
     * @param string $sessionId Session id
     * @return array|null
     */
    private function qyssoRebillReturnViewParams(array $payload, string $sessionId): ?array
    {
        if (!empty($payload['reply_code'])
            && !empty($payload['recur_seriesID'])
            && !empty($payload['recur_chargeNum'])
            && $payload['recur_chargeNum'] > 1
        ) {
            $purchaseProcess = $this->purchaseProcessHandler->load($sessionId);

            if (!$purchaseProcess->cascade()->currentBiller() instanceof QyssoBiller) {
                return null;
            }

            return [
                'response'    => [
                    'success' => ($payload['reply_code'] == QyssoBiller::REPLY_CODE_APPROVED)
                ],
                'redirectUrl' => $purchaseProcess->redirectUrl()
            ];
        }

        return null;
    }
}
