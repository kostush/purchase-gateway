<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\Authenticate;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\DTO\Authenticate\AuthenticateThreeDHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\TokenInterface;
use Tests\UnitTestCase;

class AuthenticateThreeDHttpDTOTest extends UnitTestCase
{
    /**
     * @var TokenGenerator|MockObject
     */
    private $tokenGenerator;

    /** @var CryptService|MockObject */
    private $cryptService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $tokenInterface = $this->createMock(TokenInterface::class);
        $tokenInterface->method('__toString')->willReturn('token');

        $this->tokenGenerator = $this->createMock(TokenGenerator::class);
        $this->tokenGenerator->method('generateWithGenericKey')->willReturn($tokenInterface);

        $this->cryptService = $this->createMock(CryptService::class);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure_when_a_successful_authenticate_is_done()
    {
        $expected = [
            'jwt'   => 'token',
            'pareq' => 'pareq',
            'acs'   => 'acs'
        ];

        $authenticateHttpDTO = AuthenticateThreeDHttpDTO::create(
            $this->cryptService,
            $this->tokenGenerator,
            'acs',
            'pareq',
            'sessionId'
        );
        $this->assertEquals($expected, $authenticateHttpDTO->toArray());
    }
}
