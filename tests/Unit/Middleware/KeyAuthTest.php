<?php

namespace Middleware;

use App\Http\Middleware\KeyAuth;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidRequestException;
use ProBillerNG\PurchaseGateway\Code;
use Tests\UnitTestCase;

class KeyAuthTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_should_throw_invalid_request_exception_when_malformed_site_id_is_passed()
    {
        $invalidId = $this->faker->city;
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('The given data was invalid.');

        $keyAuthMiddleware = app()->make(KeyAuth::class);
        $request           = Request::create('', null, ['siteId' => $invalidId]);

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
        $invalidId = [$this->faker->city, $this->faker->city];
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('The given data was invalid.');

        $keyAuthMiddleware = app()->make(KeyAuth::class);
        $request           = Request::create('', null, ['siteId' => $invalidId]);

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
