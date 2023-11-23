<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitCommandHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\NewMemberInitCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;
use Ramsey\Uuid\Uuid;
use Tests\IntegrationTestCase;

class NewMemberInitCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_return_a_valid_purchase_init_command_handler_object(): void
    {
        $bundle = $this->createAndAddBundleToRepository();

        $command = $this->createInitCommand(
            [
                'site'             => $this->createSite(),
                'bundleId'         => (string) $bundle->bundleId(),
                'addOnId'          => (string) $bundle->addonId(),
                'crossSaleOptions' => [
                    [
                        'bundleId' => (string) $bundle->bundleId(),
                        'addonId'  => (string) $bundle->addonId(),
                        'siteId'   => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        /** @var NewMemberInitCommandHandler $handler */
        $handler = app()->make(NewMemberInitCommandHandler::class);

        /** @var PurchaseInitCommandHttpDTO $result */
        $result = $handler->execute($command);

        $this->assertInstanceOf(PurchaseInitCommandHttpDTO::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_store_zero_as_amount_when_given_amount_is_zero(): void
    {
        $bundle = $this->createAndAddBundleToRepository();

        $command = $this->createInitCommand(
            [
                'site'             => $this->createSite(),
                'amount'           => 0,
                'bundleId'         => (string) $bundle->bundleId(),
                'addOnId'          => (string) $bundle->addonId(),
                'crossSaleOptions' => [
                    [
                        'bundleId' => (string) $bundle->bundleId(),
                        'addonId'  => (string) $bundle->addonId(),
                        'siteId'   => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        /** @var NewMemberInitCommandHandler $handler */
        $handler = app()->make(NewMemberInitCommandHandler::class);

        /** @var PurchaseInitCommandHttpDTO $result */
        $result = $handler->execute($command);

        $sessionPayload = json_decode(
            (new PurchaseProcessRepository(app('em')))
                ->findOne(Uuid::fromString($result->jsonSerialize()['sessionId']))->payload(),
            true
        );

        $this->assertEquals(0, $sessionPayload['initializedItemCollection'][0]['initialAmount']);
    }

    /**
     * @test
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_store_payment_methods_if_biller_is_of_biller_available_payment_methods(): void
    {
        $bundle = $this->createAndAddBundleToRepository();

        $command = $this->createInitCommand(
            [
                'site'             => $this->createSite(),
                'amount'           => 0,
                'bundleId'         => (string) $bundle->bundleId(),
                'addOnId'          => (string) $bundle->addonId(),
                'paymentMethod'    => 'zelle',
                'forceCascade'     => RetrieveCascadeTranslatingService::TEST_QYSSO,
                'redirectUrl'      => 'https://redirect.url',
                'crossSaleOptions' => [
                    [
                        'bundleId' => (string) $bundle->bundleId(),
                        'addonId'  => (string) $bundle->addonId(),
                        'siteId'   => self::CROSS_SALE_SITE_ID
                    ]
                ]
            ]
        );

        /** @var NewMemberInitCommandHandler $handler */
        $handler = app()->make(NewMemberInitCommandHandler::class);

        /** @var PurchaseInitCommandHttpDTO $result */
        $result = $handler->execute($command);

        self::assertContains('zelle', $result->jsonSerialize()['nextAction']['availablePaymentMethods']);
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_throw_cross_sale_site_not_exists_exception_if_cross_sale_site_is_invalid(): void
    {
        $bundle = $this->createAndAddBundleToRepository();

        $command = $this->createInitCommand(
            [
                'site'             => $this->createSite(),
                'bundleId'         => (string) $bundle->bundleId(),
                'addOnId'          => (string) $bundle->addonId(),
                'crossSaleOptions' => [
                    [
                        'bundleId' => (string) $bundle->bundleId(),
                        'addonId'  => (string) $bundle->addonId(),
                        'siteId'   => $this->faker->uuid
                    ]
                ]
            ]
        );

        /** @var NewMemberInitCommandHandler $handler */
        $handler = app()->make(NewMemberInitCommandHandler::class);

        $this->expectException(CrossSaleSiteNotExistException::class);

        $handler->execute($command);
    }
}
