<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use Tests\UnitTestCase;

class BinRoutingTest extends UnitTestCase
{
    private const ATTEMPT = 2;

    private const ROUTING_CODE = '123123';

    private const BANK_NAME = 'FirstBank';
    /**
     * @test
     * @return BinRouting
     */
    public function it_should_return_a_bin_routing_object(): BinRouting
    {
        $result = BinRouting::create(
            self::ATTEMPT,
            self::ROUTING_CODE,
            self::BANK_NAME
        );
        $this->assertInstanceOf(BinRouting::class, $result);
        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_bin_routing_object
     * @param BinRouting $binRouting Bin routing object
     * @return void
     */
    public function it_should_contain_the_number_of_attempts(BinRouting $binRouting): void
    {
        $this->assertSame(self::ATTEMPT, $binRouting->attempt());
    }

    /**
     * @test
     * @depends it_should_return_a_bin_routing_object
     * @param BinRouting $binRouting Bin routing object
     * @return void
     */
    public function it_should_contain_a_routing_code(BinRouting $binRouting): void
    {
        $this->assertSame(self::ROUTING_CODE, $binRouting->routingCode());
    }

    /**
     * @test
     * @depends it_should_return_a_bin_routing_object
     * @param BinRouting $binRouting Bin routing object
     * @return void
     */
    public function it_should_the_bank_name(BinRouting $binRouting): void
    {
        $this->assertSame(self::BANK_NAME, $binRouting->bankName());
    }
}
