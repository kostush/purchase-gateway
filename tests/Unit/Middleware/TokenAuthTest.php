<?php

namespace Middleware;

use App\Http\Middleware\TokenAuth;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use Tests\UnitTestCase;

class TokenAuthTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_should_throw_invalid_request_exception_when_malformed_site_id_is_passed()
    {
        $invalidId = $this->faker->city;
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('The given data was invalid.');

        $keyAuthMiddleware = app()->make(TokenAuth::class);
        $request           = Request::create(
            '',
            null,
            ['siteId' => $invalidId],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->faker->password(150)]
        );

        try {
            $keyAuthMiddleware->handle(
                $request,
                function () {
                }
            );
        } catch (InvalidRequestException $e) {
            $this->assertSame(
                [
                    "siteId" => [
                        0 => "The site id value $invalidId is not uuid."
                    ]
                ],
                $e->errors()
            );
            throw $e;
        }
    }

    /**
     * @test
     */
    public function it_should_throw_invalid_request_exception_when_array_is_passed_as_site_id()
    {
        $invalidId = [
            $this->faker->city,
            $this->faker->city
        ];
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('The given data was invalid.');

        $keyAuthMiddleware = app()->make(TokenAuth::class);
        $request           = Request::create(
            '',
            null,
            ['siteId' => $invalidId],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->faker->password(150)]
        );

        try {
            $keyAuthMiddleware->handle(
                $request,
                function () {
                }
            );
        } catch (InvalidRequestException $e) {
            $this->assertSame(
                [
                    "siteId" => [
                        0 => "The site id value :input is not uuid."
                    ]
                ],
                $e->errors()
            );
            throw $e;
        }
    }
}