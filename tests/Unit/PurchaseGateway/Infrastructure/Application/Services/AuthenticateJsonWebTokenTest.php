<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Application\Services;

use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AuthenticateJsonWebToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use Tests\UnitTestCase;

class AuthenticateJsonWebTokenTest extends UnitTestCase
{
    protected const SITE_ID = "8e34c94e-135f-4acb-9141-58b3a6e56c74";

    /**
     * @var AuthenticateJsonWebToken
     */
    private $authenticateJsonWebToken;

    /**
     * @var JsonWebTokenGenerator
     */
    private $jsonWebTokenGenerator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->jsonWebTokenGenerator    = new JsonWebTokenGenerator();
        $this->authenticateJsonWebToken = new AuthenticateJsonWebToken($this->jsonWebTokenGenerator);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_authenticate_if_private_key_is_used()
    {
        $site  = $this->createSite();
        $token = $this->jsonWebTokenGenerator->generateWithPrivateKey($site, []);
        $this->assertTrue($this->authenticateJsonWebToken->authenticate((string) $token, $site));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_authenticate_if_a_public_key_is_used()
    {
        $site  = $this->createSite();
        $token = $this->jsonWebTokenGenerator->generateWithPublicKey($site, 0, []);
        $this->assertFalse($this->authenticateJsonWebToken->authenticate((string) $token, $site));
    }
}
