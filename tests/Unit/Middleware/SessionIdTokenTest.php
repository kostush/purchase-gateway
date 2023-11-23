<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SessionIdToken;
use Illuminate\Routing\Route;
use ProBillerNG\Crypt\Sodium\PrivateKeyConfig;
use ProBillerNG\Crypt\Sodium\PrivateKeyCypher;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidTokenSessionException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SessionWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\SodiumCryptService;
use Ramsey\Uuid\Uuid;
use Tests\UnitTestCase;
use Illuminate\Http\Request;

class SessionIdTokenTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_decode_sessionId()
    {
        $tokenAuthService = new SessionWebToken(new JsonWebTokenGenerator());
        $cryptService     = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $tokenGenerator = new JsonWebTokenGenerator();
        $jwt            = (string) $tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $cryptService->encrypt('961fc1a5-04ca-4d7c-9e1d-e8b95833657a')
            ]
        );

        $request = \Mockery::mock(Request::class . '[route]', [[], [], [], [], [], ['REQUEST_URI' => 'testing/' . $jwt]]);
        $request->attributes->set('sessionId', $this->faker->uuid);
        $request
            ->shouldReceive('route')
            ->withNoArgs()
            ->andReturn(
                []
            );

        $request
            ->shouldReceive('route')
            ->with('jwt')
            ->andReturn(
                $jwt
            );

        $middleWare = new SessionIdToken(
            $this->createMock(PurchaseProcessHandler::class),
            $tokenAuthService,
            $cryptService
        );

        $middleWare->handle(
            $request,
            function () {
            }
        );

        $this->assertNotEmpty($request->get('sessionId'));

        return $request->get('sessionId');
    }

    /**
     * @test
     * @depends it_should_decode_sessionId
     * @return void
     * @param string $sessionId Session id
     */
    public function it_should_be_a_valid_sessionId(string $sessionId): void
    {
        $this->assertTrue(Uuid::isValid($sessionId));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_missing_token(): void
    {
        $this->expectException(InvalidTokenException::class);

        $tokenAuthService = new SessionWebToken(new JsonWebTokenGenerator());
        $cryptService     = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $request = new Request();
        $request->attributes->set('sessionId', $this->faker->uuid);

        $middleWare = new SessionIdToken(
            $this->createMock(PurchaseProcessHandler::class),
            $tokenAuthService,
            $cryptService
        );

        $middleWare->handle(
            $request,
            function () {
            }
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_invalid_session(): void
    {
        $this->expectException(InvalidTokenSessionException::class);

        $tokenAuthService = new SessionWebToken(new JsonWebTokenGenerator());
        $cryptService     = new SodiumCryptService(
            new PrivateKeyCypher(
                new PrivateKeyConfig(
                    env('APP_CRYPT_KEY')
                )
            )
        );

        $tokenGenerator = new JsonWebTokenGenerator();
        $jwt            = (string) $tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $cryptService->encrypt('invalid session')
            ]
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => 'testing/' . $jwt]);
        $request->attributes->set('sessionId', $this->faker->uuid);
        $request->setRouteResolver(
            function () use ($request) {
                return (new Route('GET', 'testing/{jwt}', []))->bind($request);
            }
        );

        $middleWare = new SessionIdToken(
            $this->createMock(PurchaseProcessHandler::class),
            $tokenAuthService,
            $cryptService
        );

        $middleWare->handle(
            $request,
            function () {
            }
        );
    }
}
