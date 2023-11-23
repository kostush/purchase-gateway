<?php

namespace Tests\Unit\Middleware;

use App\Exceptions\InvalidSessionIdException;
use App\Http\Middleware\ValidateSessionId;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\UnitTestCase;

class ValidateSessionIdTest extends UnitTestCase
{
    /**
     * @test
     * @throws InvalidSessionIdException
     * @throws \ReflectionException
     */
    public function it_should_validate_sessionId()
    {
        $request = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('route')
            ->willReturn('132f2e26-2222-445d-a8c5-7fe23a37a819');

        $request->attributes = $this->createMock(ParameterBag::class);

        $middleWare = new ValidateSessionId();

        $middleWare->handle($request, function () {});

        $this->assertNotEmpty($request->route('sessionId'));
    }

    /**
     * @test
     * @throws InvalidSessionIdException
     * @throws \ReflectionException
     */
    public function it_should_throw_exception_if_invalid_sessionId_is_provided()
    {
        $this->expectException(InvalidSessionIdException::class);

        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('route')
            ->willReturn('123456789');

        $request->attributes = $this->createMock(ParameterBag::class);

        $middleWare = new ValidateSessionId();

        $middleWare->handle($request, function () {});
    }

}
