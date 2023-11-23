<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use Tests\UnitTestCase;

class MemberIdTest extends UnitTestCase
{

    /**
     * @return string[]
     */
    public function listOfIds(): array
    {
        return [
            ['UUID' => '1110c0c4-4963-4351-8b9b-e17a72ecb084'],
            ['TJ Test Id' => 'TJ123456'],
            ['Legacy Test Id' => '123456'],
            ['Random Value' => '23ne893dnqakd3423n'],
        ];
    }

    /**
     * @test
     * @dataProvider listOfIds
     *
     * @param string $value Value
     *
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_id_object_when_any_string_value_is_provided(string $value): void
    {
        $id = MemberId::createFromString($value);

        $this->assertInstanceOf(MemberId::class, $id);
        $this->assertEquals($value, (string) $id);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_id_object_when_no_string_value_is_provided(): void
    {
        $id = MemberId::create();

        $this->assertInstanceOf(MemberId::class, $id);
        $this->assertNotEmpty((string) $id);
    }
}
