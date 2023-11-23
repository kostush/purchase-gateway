<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResponseToken
 *
 * @package App\Http\Middleware
 */
class ResponseToken
{
    /** @var TokenGenerator */
    private $tokenGenerator;

    /**
     * ResponseToken constructor.
     *
     * @param TokenGenerator $tokenGenerator Token Generator
     */
    public function __construct(TokenGenerator $tokenGenerator)
    {
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @param Request $request Request
     * @param Closure $next    Closure
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        // Only add token on valid response
        if ($response->isOk() && $request->get('site') !== null) {
            $authToken = $request->bearerToken();

            // If auth token was sent in request, return the same token, otherwise create a new one
            if ($authToken === null) {
                $siteId = $request->input('siteId');

                if (!empty($siteId)) {
                    $tokenPayload['sessionId'] = $request->attributes->get('sessionId');

                    if ($mgpgToken = $response->headers->get('X-Mgpg-Auth-Token')) {
                        $tokenPayload['X-CORRELATION-ID']           = $request->headers->get('X-CORRELATION-ID');
                        $tokenPayload['X-Mgpg-Auth-Token']          = $mgpgToken;
                        $tokenPayload['X-Mgpg-Session-Id']          = $response->headers->get('X-Mgpg-Session-Id');
                        $tokenPayload['X-Public-Key-Id']            = $response->headers->get(
                            'X-Public-Key-Id'
                        ); // Forward to Process because we can't fetch from session
                        $tokenPayload['X-Cross-Sale-Charge-Id-Map'] = $response->headers->get(
                            'X-Cross-Sale-Charge-Id-Map'
                        );
                        $tokenPayload['X-Return-Url']               = $response->headers->get('X-Return-Url');
                        $tokenPayload['X-Postback-Url']             = $response->headers->get('X-Postback-Url');

                        // They will be available inside `X-Auth-Token` for subsequent purchase calls.
                        $response->headers->remove('X-Mgpg-Session-Id');
                        $response->headers->remove('X-Mgpg-Auth-Token');
                        $response->headers->remove('X-Public-Key-Id');
                        $response->headers->remove('X-Cross-Sale-Charge-Id-Map');
                        $response->headers->remove('X-Return-Url');
                        $response->headers->remove('X-Postback-Url');
                    }

                    $authToken = $this->tokenGenerator->generateWithPrivateKey($request->get('site'), $tokenPayload);
                }
            }
            $response->headers->set('X-Auth-Token', (string) $authToken);
        }

        return $response;
    }
}
