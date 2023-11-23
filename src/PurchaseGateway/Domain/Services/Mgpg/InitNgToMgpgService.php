<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;

abstract class InitNgToMgpgService
{
    const RETURN_URL_ROUTE_NAME   = 'mgpg.threed.return';

    const POSTBACK_URL_ROUTE_NAME = 'mgpg.threed.postback';

    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;


    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * InitNgToMgpgService constructor.
     *
     * @param TokenGenerator $tokenGenerator
     * @param CryptService   $cryptService
     */
    public function __construct(
        TokenGenerator $tokenGenerator,
        CryptService $cryptService
    ) {
        $this->tokenGenerator             = $tokenGenerator;
        $this->cryptService               = $cryptService;
    }

    /**
     * @param string $clientUrl
     * @param string $publicKeyId
     * @param string $sessionId
     * @param string $correlationId
     *
     * @return string Return URL
     */
    protected function createReturnUrl(
        string $clientUrl,
        string $publicKeyId,
        string $sessionId,
        string $correlationId
    ): string {
        return $this->createUrl(self::RETURN_URL_ROUTE_NAME, $clientUrl, $publicKeyId, $sessionId, $correlationId);
    }

    /**
     * @param string $clientUrl
     * @param string $publicKeyId
     * @param string $sessionId
     * @param string $correlationId
     *
     * @return string Postback URL
     */
    protected function createPostbackUrl(
        string $clientUrl,
        string $publicKeyId,
        string $sessionId,
        string $correlationId
    ): string {
        return $this->createUrl(self::POSTBACK_URL_ROUTE_NAME, $clientUrl, $publicKeyId, $sessionId, $correlationId);
    }

    /**
     * Create URL which MGPG will call back on Biller's Return and on Biller's Postback. JWT keys are set to safe-keep
     * original client url and other attributes when constructing the DTO when those endpoints are called.
     *
     * @param string $routeName
     * @param string $clientUrl
     * @param string $publicKeyId
     * @param string $sessionId
     * @param string $correlationId
     *
     * @return string
     */
    protected function createUrl(
        string $routeName,
        string $clientUrl,
        string $publicKeyId,
        string $sessionId,
        string $correlationId
    ): string
    {
        return route(
            $routeName,
            [
                'jwt' => (string) $this->tokenGenerator->generateWithGenericKey(
                    [
                        'clientUrl' => $this->cryptService->encrypt(
                            $clientUrl
                        ),
                        'sessionId' => $this->cryptService->encrypt(
                            $sessionId
                        ),
                        'publicKeyId' => $this->cryptService->encrypt(
                            $publicKeyId
                        ),
                        'correlationId' => $this->cryptService->encrypt($correlationId),
                    ]
                )
            ]
        );
    }
}