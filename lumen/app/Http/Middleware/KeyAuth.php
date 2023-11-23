<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedRequestException;
use App\Http\Middleware\Utils\UuidValidatorTrait;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SiteNotExistException;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AuthenticateKeyTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;

class KeyAuth
{
    use UuidValidatorTrait;
    /**
     * @var AuthenticateKeyTranslatingService
     */
    protected $authenticateKeyService;

    /** @var ConfigService */
    private $configServiceClient;

    /**
     * Authentication constructor.
     *
     * @param AuthenticateKeyTranslatingService $authenticateKeyService authenticate key service
     * @param ConfigService                     $configServiceClient
     */
    public function __construct(
        AuthenticateKeyTranslatingService $authenticateKeyService,
        ConfigService $configServiceClient
    ) {
        $this->authenticateKeyService = $authenticateKeyService;
        $this->configServiceClient    = $configServiceClient;
    }

    /**
     * Run the request filter.
     *
     * @param \Illuminate\Http\Request $request Request
     * @param \Closure                 $next    next
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, \Closure $next)
    {
        // get site id
        $siteId = $request->input('siteId', '');
        if (!$siteId && $request->siteId) {
            $siteId = $request->siteId;
        }
        if (!$siteId) {
            throw new SiteNotExistException();
        }

        $validator = $this->getUuidValidator($siteId);

        if ($validator->fails()) {
            throw new InvalidRequestException($validator);
        }

        $site = $this->configServiceClient->getSite($siteId);
        if (is_null($site)) {
            throw new SiteNotExistException();
        }

        // get site's public key
        $publicKey = $request->header('x-api-key');
        if (empty($publicKey)) {
            throw new UnauthorizedRequestException(UnauthorizedRequestException::AUTH_KEY, 'Missing API key');
        }

        $publicKeyIndex = $this->authenticateKeyService->getPublicKeyIndex(
            $site,
            $publicKey
        );

        if (!isset($publicKeyIndex)) {
            throw new UnauthorizedRequestException(UnauthorizedRequestException::AUTH_KEY, 'Invalid API key');
        }

        $request->attributes->set('publicKeyId', $publicKeyIndex);
        $request->attributes->set('site', $site);

        return $next($request);
    }
}
