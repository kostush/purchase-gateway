<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO;

use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveFailedBillers\FailedBillersQueryHttpDTO;
use ProBillerNG\PurchaseGateway\Domain\Model\FailedBillers;
use Tests\UnitTestCase;

class FailedBillersQueryHttpDTOTest extends UnitTestCase
{
    /**
     * @var array
     */
    private $failedBillersArray;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->failedBillersArray = [
            ['billerName' => 'rocketgate'],
            ['billerName' => 'netbilling']
        ];
        parent::setUp();
    }

    /**
     * @test
     * @return FailedBillersQueryHttpDTO
     */
    public function it_should_call_the_to_array_method_of_the_failed_billers_model(): FailedBillersQueryHttpDTO
    {
        $failedBillers = $this->prophesize(FailedBillers::class);

        $failedBillers->toArray()->shouldBeCalled()->willReturn($this->failedBillersArray);

        return new FailedBillersQueryHttpDTO($failedBillers->reveal(), false);
    }

    /**
     * @test
     * @depends it_should_call_the_to_array_method_of_the_failed_billers_model
     * @param FailedBillersQueryHttpDTO $dto The DTO object
     * @return array
     */
    public function it_should_return_a_failed_billers_array(FailedBillersQueryHttpDTO $dto): array
    {
        $failedBillersArray = $dto->jsonSerialize();
        $this->assertIsArray($failedBillersArray);

        return $failedBillersArray;
    }

    /**
     * @test
     * @depends it_should_return_a_failed_billers_array
     * @param array $failedBillers the failed billers array
     * @return  void
     */
    public function it_should_contain_the_correct_billers_array(array $failedBillers): void
    {
        $this->assertSame(
            $failedBillers,
            [
                'was3DSUsed'    => false,
                'failedBillers' => $this->failedBillersArray
            ]
        );
    }

    /**
     * @test
     * @depends it_should_call_the_to_array_method_of_the_failed_billers_model
     * @param FailedBillersQueryHttpDTO $dto The DTO object
     * @return void
     */
    public function it_should_return_a_instance_of_json_serializable(FailedBillersQueryHttpDTO $dto): void
    {
        $this->assertInstanceOf(\JsonSerializable::class, $dto);
    }
}
