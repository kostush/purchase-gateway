<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\DTO\Purchase\Process;

use Lcobucci\JWT\Parser;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\IntegrationTestCase;

class PostbackResponseDTOTest extends IntegrationTestCase
{
    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /** @var SessionId */
    private $sessionId;

    /** @var TransactionId */
    private $mainTransactionId;

    /** @var TransactionId */
    private $firstCrossSaleTransactionId;

    /** @var TransactionId */
    private $secondCrossSaleTransactionId;

    /** @var Site */
    private $site;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenGenerator               = app(TokenGenerator::class);
        $this->mainTransactionId            = TransactionId::createFromString('670af402-2956-11e9-b210-d663bd873d93');
        $this->firstCrossSaleTransactionId  = TransactionId::createFromString('3ea5d655-9a2c-466e-9b82-048fe57bcea1');
        $this->secondCrossSaleTransactionId = TransactionId::createFromString('b273aa71-4d68-4645-9656-4b01ec12a7ca');
        $this->sessionId                    = SessionId::createFromString('1cb5d7a5-841a-41cd-af8c-e47c044dee02');
        $this->site                         = $this->createSite();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_have_valid_digest(): void
    {
        $dto             = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $publicKeyIndex  = 0;
        $mainProduct     = $this->createMock(InitializedItem::class);
        $firstCrossSale  = $this->createMock(InitializedItem::class);
        $secondCrossSale = $this->createMock(InitializedItem::class);

        $dto->method('jsonSerialize')->willReturn(
            [

                "success"        => true,
                "purchaseId"     => $this->faker->uuid,
                "memberId"       => $this->faker->uuid,
                "bundleId"       => $this->faker->uuid,
                "addonId"        => $this->faker->uuid,
                "itemId"         => $this->faker->uuid,
                "subscriptionId" => $this->faker->uuid,
                "transactionId"  => $this->mainTransactionId,
                "billerName"     => RocketgateBiller::BILLER_NAME,
                "sessionId"      => $this->faker->uuid,
                "crossSells"     => [
                    [
                        "success"        => true,
                        "bundleId"       => $this->faker->uuid,
                        "addonId"        => $this->faker->uuid,
                        "subscriptionId" => $this->faker->uuid,
                        "itemId"         => $this->faker->uuid,
                        "transactionId"  => $this->firstCrossSaleTransactionId
                    ],
                    [
                        "success"        => true,
                        "bundleId"       => $this->faker->uuid,
                        "addonId"        => $this->faker->uuid,
                        "subscriptionId" => $this->faker->uuid,
                        "itemId"         => $this->faker->uuid,
                        "transactionId"  => $this->firstCrossSaleTransactionId
                    ],
                ],
                "digest"         => "some-random-string", // will be replaced on postback dto
            ]
        );

        $dto->method('site')->willReturn($this->site);
        $mainProduct->method('lastTransactionId')->willReturn($this->mainTransactionId);
        $mainProduct->method('siteId')->willReturn($this->site->siteId());
        $firstCrossSale->method('lastTransactionId')->willReturn($this->firstCrossSaleTransactionId);
        $secondCrossSale->method('lastTransactionId')->willReturn($this->secondCrossSaleTransactionId);

        $postbackResponseDto = PostbackResponseDto::createFromResponseData(
            $dto,
            $this->tokenGenerator,
            $publicKeyIndex,
            $this->sessionId,
            $mainProduct,
            [$firstCrossSale, $secondCrossSale]
        );

        $postbackResponseArray = $postbackResponseDto->jsonSerialize();

        // prepare dto response actual hash
        $actualDigest = $postbackResponseArray['digest'];
        $jwtToken     = (new Parser())->parse($actualDigest);
        $actualHash   = $jwtToken->getClaim('hash');
        
        // prepare expected hash
        unset($postbackResponseArray['digest']);
        $expectedHash = hash('sha512', json_encode($postbackResponseArray));

        $this->assertEquals($expectedHash, $actualHash);
    }
}
