<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine;

use ProBillerNG\PurchaseGateway\Application\Services\SessionVersionConverter;
use ProBillerNG\PurchaseGateway\Domain\Model\Session\SessionInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\ConvertingPurchaseProcessRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PurchaseProcessRepository;
use Ramsey\Uuid\Uuid;
use Tests\UnitTestCase;

class ConvertingSessionRepositoryTest extends UnitTestCase
{
    /**
     * @var PurchaseProcessRepository
     */
    private $sessionRepository;

    /**
     * @var SessionVersionConverter
     */
    private $converter;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->converter         = $this->prophesize(SessionVersionConverter::class);
        $this->sessionRepository = $this->prophesize(PurchaseProcessRepository::class);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     * @return void
     */
    public function find_one_should_call_find_one_session_repository_method(): void
    {
        $sessionId = Uuid::uuid4();

        $this->sessionRepository->findOne($sessionId)->shouldBeCalled()->willReturn(
            SessionInfo::create(
                $this->faker->uuid,
                json_encode([])
            )
        );
        $this->converter->convert([])->willReturn([]);

        $convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            $this->sessionRepository->reveal(),
            $this->converter->reveal()
        );
        $convertingSessionRepository->findOne($sessionId);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     * @return void
     */
    public function find_one_should_return_null_when_session_id_is_not_found(): void
    {
        $sessionId = Uuid::uuid4();
        $this->sessionRepository->findOne($sessionId)->willReturn(null);
        $convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            $this->sessionRepository->reveal(),
            $this->converter->reveal()
        );

        $result = $convertingSessionRepository->findOne($sessionId);

        $this->assertNull($result);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     * @return void
     */
    public function find_one_should_not_call_converter_convert_method_when_session_id_is_not_found(): void
    {
        $sessionId = Uuid::uuid4();
        $this->sessionRepository->findOne($sessionId)->willReturn(null);
        $this->converter->convert()->shouldNotBeCalled();
        $convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            $this->sessionRepository->reveal(),
            $this->converter->reveal()
        );
        $convertingSessionRepository->findOne($sessionId);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     * @return void
     */
    public function find_one_should_call_converter_convert_method_when_session_id_is_found(): void
    {
        $sessionId = Uuid::uuid4();
        $this->sessionRepository->findOne($sessionId)->willReturn(
            SessionInfo::create(
                $this->faker->uuid,
                json_encode([])
            )
        );
        $this->converter->convert([])->shouldBeCalled();
        $convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            $this->sessionRepository->reveal(),
            $this->converter->reveal()
        );
        $convertingSessionRepository->findOne($sessionId);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     * @return void
     */
    public function find_one_should_manipulate_session_info_object_payload_attribute(): void
    {
        $sessionInfo = $this->prophesize(SessionInfo::class);
        $testJson    = '{"test":[]}';

        $sessionInfo->payload()->shouldBeCalled()->willReturn($testJson);
        $sessionInfo->setPayload([])->shouldBeCalled();

        $sessionId = Uuid::uuid4();
        $this->sessionRepository->findOne($sessionId)->willReturn($sessionInfo->reveal());
        $this->converter->convert(json_decode($testJson, true))->willReturn([]);
        $convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            $this->sessionRepository->reveal(),
            $this->converter->reveal()
        );
        $convertingSessionRepository->findOne($sessionId);
    }

    /**
     * @test
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException
     * @return void
     */
    public function find_one_should_return_session_info_object(): void
    {
        $sessionId = Uuid::uuid4();

        $this->sessionRepository->findOne($sessionId)->willReturn(
            SessionInfo::create(
                $this->faker->uuid,
                json_encode([])
            )
        );
        $this->converter->convert([])->willReturn([]);
        $convertingSessionRepository = new ConvertingPurchaseProcessRepository(
            $this->sessionRepository->reveal(),
            $this->converter->reveal()
        );

        $result = $convertingSessionRepository->findOne($sessionId);

        $this->assertInstanceOf(SessionInfo::class, $result);
    }
}
