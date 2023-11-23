<?php


namespace Tests\Unit\Middleware;

use App\Http\Middleware\GenerateSessionId;
use App\Http\Middleware\ValidateSessionId;
use Illuminate\Http\Request;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventBackupCommandHandler;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\UnitTestCase;

class GenerateSessionIdTest extends UnitTestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_generate_sessionId()
    {
        $request = new Request();

        $middleWare = new GenerateSessionId();

        $middleWare->handle($request, function () {});

        $this->assertNotEmpty($request->get('sessionId'));

        return $request->get('sessionId');
    }

    /**
     * @test
     * @depends it_should_generate_sessionId
     * @param string $sessionId string
     */
    public function it_should_generate_valid_sessionId(string $sessionId)
    {
        $this->assertTrue(Uuid::isValid($sessionId));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_not_override_the_existing_sessionId()
    {
        $sessionId = '24e9912f-cb91-4ff4-b779-08352bbc4ee9';
        $request   = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('route')
            ->with('sessionId')
            ->willReturn($sessionId);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('get')->with('sessionId')->willReturn($sessionId);

        $middleWare = new GenerateSessionId();
        $middleWare->handle($request, function () {});

        $this->assertEquals($request->attributes->get('sessionId'), $sessionId);

        return $request->get('sessionId');
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_generate_sessionId_if_the_given_one_is_invalid_uuid()
    {
        $sessionId = '{{some invalid session}}';

        $handler = $this->getMockBuilder(GenerateSessionId::class)->getMock();

        $reflection = new \ReflectionClass(GenerateSessionId::class);
        $method     = $reflection->getMethod('initSession');
        $method->setAccessible(true);

        $result = $method->invokeArgs($handler, [$sessionId]);

        $this->assertNotEquals($result, $sessionId);
        $this->assertTrue(Uuid::isValid($result));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_generate_sessionId_if_the_given_one_is_empty()
    {
        $handler = $this->getMockBuilder(GenerateSessionId::class)->getMock();

        $reflection = new \ReflectionClass(GenerateSessionId::class);
        $method     = $reflection->getMethod('initSession');
        $method->setAccessible(true);

        $result = $method->invokeArgs($handler, [null]);

        $this->assertNotEmpty($result);
        $this->assertTrue(Uuid::isValid($result));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_not_generate_sessionId_if_the_given_one_is_valid()
    {
        $sessionId = (string) Uuid::uuid4();
        $handler = $this->getMockBuilder(GenerateSessionId::class)->getMock();

        $reflection = new \ReflectionClass(GenerateSessionId::class);
        $method     = $reflection->getMethod('initSession');
        $method->setAccessible(true);

        $result = $method->invokeArgs($handler, [$sessionId]);

        $this->assertEquals($sessionId, $result);
    }
}
