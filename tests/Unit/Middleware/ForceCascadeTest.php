<?php
declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ForceCascade;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ForceCascadeException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\UnitTestCase;
use Illuminate\Http\Request;

class ForceCascadeTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws ForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_validate_forceCascade_header(): void
    {
        $this->expectException(ForceCascadeException::class);
        $request = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('header')
            ->willReturn('invalidForceCascade');

        $request->attributes = $this->createMock(ParameterBag::class);

        $middleWare = new ForceCascade();

        $middleWare->handle($request, function () {});
    }
}
