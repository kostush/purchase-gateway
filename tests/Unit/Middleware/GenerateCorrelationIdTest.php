<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\GenerateCorrelationId;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Tests\UnitTestCase;

class GenerateCorrelationIdTest extends UnitTestCase
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
     * @var GenerateCorrelationId
     */
    private $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->uuid       = $this->faker->uuid;
        $this->request    = app(Request::class);
        $this->middleware = app(GenerateCorrelationId::class);
    }

    /**
     * @test
     */
    public function it_should_generate_valid_correlation_id_header_if_none_provided()
    {
        $this->middleware->handle(
            $this->request,
            function (Request $req) {
                $this->assertTrue(
                    Uuid::isValid(
                        $req->headers->get(self::CORRELATION_ID_HEADER_KEY)
                    )
                );
            }
        );
    }

    /**
     * @test
     */
    public function it_should_not_override_valid_correlation_id_header_value()
    {
        $this->request->headers->set(self::CORRELATION_ID_HEADER_KEY, $this->uuid);

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
    public function it_should_generate_correlation_id_header_if_provided_value_is_invalid_uuid()
    {
        $this->request->headers->set(self::CORRELATION_ID_HEADER_KEY, 'someinvalidvalue');

        $this->middleware->handle(
            $this->request,
            function (Request $req) {
                $correlationId = $req->headers->get(self::CORRELATION_ID_HEADER_KEY);
                $this->assertNotEquals($this->uuid, $correlationId);
                $this->assertTrue(Uuid::isValid($correlationId));
            }
        );
    }
}
