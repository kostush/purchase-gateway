<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\Correlation;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use Tests\UnitTestCase;

class CorrelationTest extends UnitTestCase
{
    const CORRELATION_ID_HEADER_KEY = 'X-CORRELATION-ID';

    /**
     * @var string
     */
    private $uuid;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var Correlation
     */
    private $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid           = $this->faker->uuid;
        $this->request        = app(Request::class);
        $this->tokenGenerator = app(TokenGenerator::class);
        $this->middleware     = app(Correlation::class);

        $cryptKey    = env('APP_CRYPT_KEY');
        $this->token = (string) $this->tokenGenerator->generateWithGenericKey(
            [self::CORRELATION_ID_HEADER_KEY => $this->uuid],
            $cryptKey
        );

        $this->emptyToken = (string) $this->tokenGenerator->generateWithGenericKey([], $cryptKey);
    }

    /**
     * @test
     */
    public function it_should_set_as_header_correlation_id_parsed_from_jwt_token()
    {
        $this->request->headers->set('Authorization', 'Bearer ' . $this->token);

        $this->middleware->handle(
            $this->request,
            function (Request $req) {
                $this->assertEquals($this->uuid, $req->headers->get(self::CORRELATION_ID_HEADER_KEY));
            }
        );
    }

    /**
     * @test
     */
    public function it_should_void_if_jwt_token_not_provided()
    {
        $this->middleware->handle(
            $this->request,
            function (Request $req) {
                $this->assertEmpty($req->headers->get(self::CORRELATION_ID_HEADER_KEY));
            }
        );
    }

    /**
     * @test
     */
    public function it_should_void_if_correlation_id_not_provided_in_jwt_token()
    {
        $this->request->headers->set('Authorization', 'Bearer ' . $this->emptyToken);

        $this->middleware->handle(
            $this->request,
            function (Request $req) {
                $this->assertEmpty($req->headers->get(self::CORRELATION_ID_HEADER_KEY));
            }
        );
    }
}
