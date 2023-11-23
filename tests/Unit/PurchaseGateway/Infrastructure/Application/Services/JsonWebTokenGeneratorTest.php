<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Application\Services;

use Lcobucci\JWT\Signer\Key\InMemory;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use Tests\UnitTestCase;

class JsonWebTokenGeneratorTest extends UnitTestCase
{
    protected const SITE_ID          = "8e34c94e-135f-4acb-9141-58b3a6e56c74";

    /**
     * @var JsonWebTokenGenerator
     */
    private $jsonWebTokenGenerator;

    private $privateKey;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->privateKey = $this->faker->uuid;
        $this->jsonWebTokenGenerator = new JsonWebTokenGenerator();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function generateWithPublicKeyTest(): void
    {
        $site  = $this->createSiteWithKeys();
        $token = $this->jsonWebTokenGenerator->generateWithPublicKey(
            $site,
            0,
            []
        );
        $this->assertTrue(
            $token->verify(
                $this->jsonWebTokenGenerator->getSigner(),
                InMemory::base64Encoded(base64_encode($_ENV['PAYSITES_API_KEY']))
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function generateWithPrivateKeyTest()
    {
        $site  = $this->createSiteWithKeys();
        $token = $this->jsonWebTokenGenerator->generateWithPrivateKey($site, []);
        $this->assertTrue(
            $token->verify(
                $this->jsonWebTokenGenerator->getSigner(),
                InMemory::base64Encoded(base64_encode($this->privateKey))
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_a_valid_jason_web_token_using_generate_wth_generic_key_method(): void
    {
        $encriptionKey = $this->faker->uuid;

        $token = $this->jsonWebTokenGenerator->generateWithGenericKey(
            ['test' => 'someTestValue'],
            $encriptionKey
        );

        $this->assertTrue(
            $token->verify(
                $this->jsonWebTokenGenerator->getSigner(),
                InMemory::base64Encoded(base64_encode($encriptionKey))
            )
        );
    }

    /**
     * @return Site
     * @throws \Exception
     */
    private function createSiteWithKeys()
    {
        $site = Site::create(
            SiteId::create(),
            BusinessGroupId::create(),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            '',
            '',
            '',
            '',
            '',
            $this->createServiceCollection(),
            $this->privateKey,
            $this->createPublicKeyCollection(),
            'Business group descriptor',
            false,
            false,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        return $site;
    }

    /**
     * @return ServiceCollection
     */
    private function createServiceCollection(): ServiceCollection
    {
        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(
            Service::create('Service name', true)
        );

        return $serviceCollection;
    }


    /**
     * @return PublicKeyCollection
     * @throws \Exception
     */
    private function createPublicKeyCollection(): PublicKeyCollection
    {
        $publicKeyCollection = new PublicKeyCollection();

        $publicKeyCollection->add(
            PublicKey::create(
                KeyId::createFromString($_ENV['PAYSITES_API_KEY']),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2019-11-15 16:11:41.0000')
            )
        );

        return $publicKeyCollection;
    }
}
