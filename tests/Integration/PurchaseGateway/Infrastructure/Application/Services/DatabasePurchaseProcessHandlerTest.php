<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Application\Services;

use ProBillerNG\PurchaseGateway\Application\Services\SessionVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Domain\Repository\PurchaseProcessRepositoryInterface;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\DatabasePurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\ConvertingPurchaseProcessRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;
use Tests\IntegrationTestCase;

class DatabasePurchaseProcessHandlerTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InitPurchaseInfoNotFoundException
     */
    public function it_should_return_purchase_process_when_retrieving_latest_version_session(): void
    {
        $sessionId = $this->faker->uuid;

        $sessionRepo = $this->createMock(PurchaseProcessRepository::class);
        $sessionRepo->method('findOne')
            ->willReturn(SessionInfo::create($sessionId, $this->latestVersionSessionPayload()));

        /** @var PurchaseProcessRepositoryInterface $convertingSessionRepo */
        $convertingSessionRepo = $this->getMockBuilder(ConvertingPurchaseProcessRepository::class)
            ->setConstructorArgs(
                [
                    $sessionRepo,
                    new SessionVersionConverter()
                ]
            )
            ->setMethods(null)
            ->getMock();


        $dbSessionHandler = new DatabasePurchaseProcessHandler($convertingSessionRepo);
        $purchaseProcess  = $dbSessionHandler->load($sessionId);

        $this->assertInstanceOf(PurchaseProcess::class, $purchaseProcess);
    }
}
