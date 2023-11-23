<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\PostbackResponseDto;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Tests\UnitTestCase;

class PostbackResponseDTOTest extends UnitTestCase
{
    const DIGEST = 'postback-digest-string';

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

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface       = $this->createMock(JsonWebToken::class);
        $tokenInterface->method('__toString')->willReturn(self::DIGEST);
        $this->tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);
        $this->mainTransactionId            = TransactionId::createFromString('670af402-2956-11e9-b210-d663bd873d93');
        $this->firstCrossSaleTransactionId  = TransactionId::createFromString('3ea5d655-9a2c-466e-9b82-048fe57bcea1');
        $this->secondCrossSaleTransactionId = TransactionId::createFromString('b273aa71-4d68-4645-9656-4b01ec12a7ca');
        $this->sessionId                    = SessionId::createFromString('1cb5d7a5-841a-41cd-af8c-e47c044dee02');
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_postback_response_instance(): array
    {
        $dto             = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $publicKeyIndex  = 0;
        $mainProduct     = $this->createMock(InitializedItem::class);
        $firstCrossSale  = $this->createMock(InitializedItem::class);
        $secondCrossSale = $this->createMock(InitializedItem::class);

        $dto->method('jsonSerialize')->willReturn(
            [

                'success'        => true,
                'purchaseId'     => $this->faker->uuid,
                'memberId'       => $this->faker->uuid,
                'bundleId'       => $this->faker->uuid,
                'addonId'        => $this->faker->uuid,
                'itemId'         => $this->faker->uuid,
                'subscriptionId' => $this->faker->uuid,
                'transactionId'  => (string) $this->mainTransactionId,
                'billerName'     => RocketgateBiller::BILLER_NAME,
                'crossSells'     => [
                    [
                        'success'        => true,
                        'bundleId'       => $this->faker->uuid,
                        'addonId'        => $this->faker->uuid,
                        'subscriptionId' => $this->faker->uuid,
                        'itemId'         => $this->faker->uuid,
                        'transactionId'  => $this->firstCrossSaleTransactionId,
                    ],
                    [
                        'success'        => true,
                        'bundleId'       => $this->faker->uuid,
                        'addonId'        => $this->faker->uuid,
                        'subscriptionId' => $this->faker->uuid,
                        'itemId'         => $this->faker->uuid,
                        'transactionId'  => $this->secondCrossSaleTransactionId
                    ],
                ],
                'digest'         => 'some-random-string',
            ]
        );

        $postbackDto = PostbackResponseDto::createFromResponseData(
            $dto,
            $this->tokenGenerator,
            $publicKeyIndex,
            $this->sessionId,
            $mainProduct,
            [$firstCrossSale, $secondCrossSale]
        );

        $this->assertInstanceOf(PostbackResponseDto::class, $postbackDto);

        return $postbackDto->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_the_session_id(array $postbackResponseArray): void
    {
        $this->assertEquals((string) $this->sessionId, $postbackResponseArray['sessionId']);
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_purchase_process_main_product_keys(array $postbackResponseArray): void
    {
        foreach ([
                     'success',
                     'purchaseId',
                     'memberId',
                     'bundleId',
                     'addonId',
                     'itemId',
                     'subscriptionId',
                     'transactionId',
                     'billerName',
                     'crossSells',
                 ] as $key) {
            $this->assertArrayHasKey($key, $postbackResponseArray);
        }
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_purchase_process_cross_sale_keys(array $postbackResponseArray): void
    {
        foreach ([
                     'success',
                     'bundleId',
                     'addonId',
                     'itemId',
                     'subscriptionId',
                     'transactionId'
                 ] as $key) {
            $this->assertArrayHasKey($key, $postbackResponseArray['crossSells'][0]);
        }
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_the_main_transaction_id(array $postbackResponseArray): void
    {
        $this->assertEquals((string) $this->mainTransactionId, $postbackResponseArray['transactionId']);
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_the_main_transaction_biller_name(array $postbackResponseArray): void
    {
        $this->assertEquals(RocketgateBiller::BILLER_NAME, $postbackResponseArray['billerName']);
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_the_first_cross_sale_transaction_id(array $postbackResponseArray): void
    {
        $this->assertEquals(
            (string) $this->firstCrossSaleTransactionId,
            $postbackResponseArray['crossSells'][0]['transactionId']
        );
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_the_second_cross_sale_transaction_id(array $postbackResponseArray): void
    {
        $this->assertEquals(
            (string) $this->secondCrossSaleTransactionId,
            $postbackResponseArray['crossSells'][1]['transactionId']
        );
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance
     * @param array $postbackResponseArray Postback Response Array
     * @return void
     */
    public function it_should_contain_the_digest(array $postbackResponseArray): void
    {
        $this->assertEquals(self::DIGEST, $postbackResponseArray['digest']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_postback_without_cross_sells_when_main_purchase_is_not_successful(): void
    {
        $dto             = $this->createMock(ProcessPurchaseGeneralHttpDTO::class);
        $publicKeyIndex  = 0;
        $mainProduct     = $this->createMock(InitializedItem::class);
        $firstCrossSale  = $this->createMock(InitializedItem::class);
        $secondCrossSale = $this->createMock(InitializedItem::class);

        $dto->method('jsonSerialize')->willReturn(
            [

                'success'        => false,
                'purchaseId'     => $this->faker->uuid,
                'memberId'       => $this->faker->uuid,
                'bundleId'       => $this->faker->uuid,
                'addonId'        => $this->faker->uuid,
                'itemId'         => $this->faker->uuid,
                'subscriptionId' => $this->faker->uuid,
                'transactionId'  => $this->mainTransactionId,
                'billerName'     => RocketgateBiller::BILLER_NAME,
                'digest'         => 'some-random-string',
            ]
        );

        $mainProduct->method('lastTransactionId')->willReturn($this->mainTransactionId);
        $mainProduct->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $postbackDto = PostbackResponseDto::createFromResponseData(
            $dto,
            $this->tokenGenerator,
            $publicKeyIndex,
            $this->sessionId,
            $mainProduct,
            [$firstCrossSale, $secondCrossSale]
        );

        $this->assertArrayNotHasKey('crossSells', $postbackDto->jsonSerialize());
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_postback_response_instance_when_user_info_is_received(): array
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
                "billerName"     => 'rocktegate',
                "crossSells"     => [
                    [
                        "success"        => true,
                        "bundleId"       => $this->faker->uuid,
                        "addonId"        => $this->faker->uuid,
                        "subscriptionId" => $this->faker->uuid,
                        "itemId"         => $this->faker->uuid,
                    ],
                    [
                        "success"        => true,
                        "bundleId"       => $this->faker->uuid,
                        "addonId"        => $this->faker->uuid,
                        "subscriptionId" => $this->faker->uuid,
                        "itemId"         => $this->faker->uuid,
                    ],
                ],
                "username" => "username",
                "password" => "password",
                "digest"         => "some-random-string",
            ]
        );

        $mainProduct->method('lastTransactionId')->willReturn($this->mainTransactionId);
        $mainProduct->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $firstCrossSale->method('lastTransactionId')->willReturn($this->firstCrossSaleTransactionId);
        $secondCrossSale->method('lastTransactionId')->willReturn($this->secondCrossSaleTransactionId);
        $userInfo = $this->createMock(UserInfo::class);
        $userInfo->method('username')->willReturn(Username::create('username'));
        $userInfo->method('password')->willReturn(Password::create('password'));

        $postbackDto = PostbackResponseDto::createFromResponseData(
            $dto,
            $this->tokenGenerator,
            $publicKeyIndex,
            $this->sessionId,
            $mainProduct,
            [$firstCrossSale, $secondCrossSale],
            $userInfo
        );

        $this->assertInstanceOf(PostbackResponseDto::class, $postbackDto);

        return $postbackDto->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance_when_user_info_is_received
     * @param array $postback Postback
     * @return void
     */
    public function it_should_contain_username_in_postback_when_user_info_is_received(array $postback): void
    {
        $this->assertEquals("username", $postback['username']);
    }

    /**
     * @test
     * @depends it_should_return_postback_response_instance_when_user_info_is_received
     * @param array $postback Postback
     * @return void
     */
    public function it_should_contain_password_in_postback_when_user_info_is_received(array $postback): void
    {
        $this->assertEquals("password", $postback['password']);
    }
}
