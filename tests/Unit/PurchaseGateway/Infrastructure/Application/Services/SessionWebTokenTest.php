<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Application\Services;

use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TokenIsExpiredException;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SessionWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use Tests\UnitTestCase;

class SessionWebTokenTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_token_expired_when_decode_token()
    {
        $sessionWebToken = new SessionWebToken(new JsonWebTokenGenerator());

        $sessionWebToken->decodeToken($this->getJwtToken());

        $this->assertTrue($sessionWebToken->checkIsExpired());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_invalid_token_when_decode_token()
    {
       // $this->expectException(InvalidTokenException::class);

        $key = base64_encode('12345678901234567890123456789012');

        $cryptService = new SodiumCryptService(new PrivateKeyCypher(
            new PrivateKeyConfig($key)
        ));
        $tokenGenerator = new JsonWebTokenGenerator();

        $jwt = (string) $tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $cryptService->encrypt('0017162f-d070-4125-a5b4-5d373ef59e86')
            ]
        );

        $sessionWebToken = new SessionWebToken(new JsonWebTokenGenerator());
        $sessionWebToken->decodeToken($jwt, $key);

        $this->assertFalse($sessionWebToken->isValid());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_valid_session_when_valid_token_is_received()
    {
        $uncryptedSession = $this->faker->uuid;

        $cryptService = new SodiumCryptService(new PrivateKeyCypher(
            new PrivateKeyConfig(
                env('APP_CRYPT_KEY')
            )
        ));
        $cryptedSession = $cryptService->encrypt($uncryptedSession);

        $tokenGenerator = new JsonWebTokenGenerator();

        $jwt = (string) $tokenGenerator->generateWithGenericKey(['sessionId' => $cryptedSession]);

        $sessionWebToken = new SessionWebToken(new JsonWebTokenGenerator());
        $sessionWebToken->decodeToken($jwt);

        $this->assertEquals($cryptedSession, $sessionWebToken->sessionId());
    }
}