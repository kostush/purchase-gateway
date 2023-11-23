<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine;

use Doctrine\DBAL\DBALException;
use ProBillerNG\PurchaseGateway\Application\Services\SessionVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\ConvertingPurchaseProcessRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;
use Ramsey\Uuid\Uuid;
use Tests\IntegrationTestCase;
use Tests\Unit\PurchaseGateway\Application\Services\SessionVersionConverterTest;

class ConvertingSessionRepositoryTest extends IntegrationTestCase
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var ConvertingPurchaseProcessRepository
     */
    private $convertingSessionRepository;

    /**
     * Setup method
     * @return void
     */
    protected function setUp(): void
    {
        $this->convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            new PurchaseProcessRepository(app('em')),
            new SessionVersionConverter()
        );

        parent::setUp();
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @return void
     */
    public function find_one_should_return_a_session_info_object_after_payload_conversion(): void
    {
        $this->sessionId = $this->faker->uuid;

        $this->convertingSessionRepository->create(
            SessionInfo::create(
                $this->sessionId,
                \json_encode(
                    SessionVersionConverterTest::$sessionLatestVersion
                ),
                new \DateTime()
            )
        );

        $sessionInfo = $this->convertingSessionRepository->findOne(Uuid::fromString($this->sessionId));
        $this->assertInstanceOf(SessionInfo::class, $sessionInfo);
    }

    /**
     * @test
     * @throws \Exception
     * @return string $sessionId The session Id
     */
    public function create_should_return_true_when_valid_session_info_object_is_provided(): string
    {
        $sessionId = $this->faker->uuid;

        $result = $this->convertingSessionRepository->create(
            SessionInfo::create(
                $sessionId,
                \json_encode(['version' => 5]),
                new \DateTime()
            )
        );

        $this->assertTrue($result);

        return $sessionId;
    }

    /**
     * @test
     * @param string $sessionId The session id
     * @depends create_should_return_true_when_valid_session_info_object_is_provided
     * @throws \Doctrine\DBAL\DBALException
     * @return void
     */
    public function update_should_return_true_when_valid_session_info_object_is_provided($sessionId): void
    {
        $sessionInfo = SessionInfo::create(
            $sessionId,
            \json_encode(['version' => 5]),
            new \DateTime()
        );

        $result = $this->convertingSessionRepository->update($sessionInfo);

        $this->assertTrue($result);
    }

    /**
     * @test
     * @param string $sessionId The session id
     * @throws \Exception
     * @depends create_should_return_true_when_valid_session_info_object_is_provided
     * @return void
     */
    public function create_should_throw_exception_when_invalid_session_info_object_is_provided($sessionId): void
    {
        //TODO update this test after a specialized domain exception is thrown
        $this->expectException(DBALException::class);

        $this->convertingSessionRepository->create(
            SessionInfo::create(
                $sessionId,
                \json_encode(['version' => 5]),
                new \DateTime()
            )
        );
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function update_should_throw_exception_when_invalid_session_info_object_is_provided(): void
    {
        //TODO update this test after a specialized domain exception is thrown
        $this->expectException(\TypeError::class);

        $sessionInfo = $this->prophesize(SessionInfo::class);
        $sessionInfo->id()->willReturn($this->faker->uuid);
        $sessionInfo->payload()->willReturn(null);

        $this->convertingSessionRepository->update($sessionInfo->reveal());
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @return void
     */
    public function retrieve_session_between_should_return_a_session_info_object_after_payload_conversion(): void
    {
        $this->sessionId = $this->faker->uuid;

        $oldsDate = new \DateTimeImmutable();
        $oldsDate = $oldsDate->setTimestamp($oldsDate->getTimestamp() - 1800);

        $startDate = new \DateTimeImmutable();
        $startDate = $startDate->sub(new \DateInterval('PT40M'));

        $date = new \DateTime();
        $date = $date->sub(new \DateInterval('PT35M'));

        $this->convertingSessionRepository->create(
            SessionInfo::create(
                $this->sessionId,
                \json_encode(
                    SessionVersionConverterTest::$sessionLatestVersion
                ),
                $date
            )
        );


        $sessionInfo = $this->convertingSessionRepository->retrieveSessionsBetween(
            $startDate,
            $oldsDate,
            10
        );

        $this->assertInstanceOf(SessionInfo::class, $sessionInfo[0]);
    }
}
