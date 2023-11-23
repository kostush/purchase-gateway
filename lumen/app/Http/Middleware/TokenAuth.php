<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedRequestException;
use App\Http\Middleware\Utils\UuidValidatorTrait;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ProBillerNG\PurchaseGateway\Application\Services\AuthenticateToken;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;

class TokenAuth
{
    use UuidValidatorTrait;

    /**
     * @var AuthenticateToken
     */
    private $tokenAuthService;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * TokenAuth constructor.
     *
     * @param AuthenticateToken $tokenAuthService Token Auth Service.
     * @param ConfigService     $configServiceClient
     */
    public function __construct(AuthenticateToken $tokenAuthService, ConfigService $configServiceClient)
    {
        $this->tokenAuthService    = $tokenAuthService;
        $this->configServiceClient = $configServiceClient;
    }

    /**
     * @param \Illuminate\Http\Request $request Request.
     * @param \Closure                 $next    Next
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {
        // Get auth token from request
        $authToken = $request->bearerToken();

        // If auth token missing, reject
        if (empty($authToken)) {
            throw new UnauthorizedRequestException(UnauthorizedRequestException::AUTH_TOKEN, 'Missing auth token');
        }

        // Get Site id
        $siteId = $request->input('siteId', '');

        // Missing site id, reject
        if (empty($siteId)) {
            return response('Missing site id', HttpResponse::HTTP_BAD_REQUEST);
        }

        $validator = $this->getUuidValidator($siteId);

        if ($validator->fails()) {
            throw new InvalidRequestException($validator);
        }

        $site      = $this->configServiceClient->getSite($siteId);

        if (is_null($site)) {
            throw new SiteNotExistException();
        }

        $success = $this->tokenAuthService->authenticate($authToken, $site);

        if (!$success) {
            throw new UnauthorizedRequestException(UnauthorizedRequestException::AUTH_TOKEN, 'Invalid auth token');
        }

        $request->attributes->set('site', $site);

        return $next($request);
    }
}
