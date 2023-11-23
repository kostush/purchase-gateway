<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Id;
use Tests\UnitTestCase;

class IdTest extends UnitTestCase
{
    /** @var MockObject|Id */
    private $id;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->id = $this->getMockBuilder(Id::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_throw_an_invalid_argument_exception_if_invalid_id_given()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->id::createFromString('123456');
    }

    /**
     * @test
     * @return Id
     */
    public function it_should_return_an_id_object_when_a_valid_uuid_is_provided(): Id
    {
        $id = $this->id::createFromString('1110c0c4-4963-4351-8b9b-e17a72ecb084');

        $this->assertInstanceOf(Id::class, $id);

        return $id;
    }

    /**
     * @test
     * @depends it_should_return_an_id_object_when_a_valid_uuid_is_provided
     *
     * @param Id $id Id
     *
     * @return void
     */
    public function it_should_contain_correct_id(Id $id)
    {
        $this->assertEquals('1110c0c4-4963-4351-8b9b-e17a72ecb084', (string) $id);
    }
}
