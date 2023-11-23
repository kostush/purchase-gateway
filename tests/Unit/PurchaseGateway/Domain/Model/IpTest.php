<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use Tests\UnitTestCase;

class IpTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws Exception
     */
    public function it_should_throw_an_invalid_ip_exception_if_invalid_ip_given()
    {
        $this->expectException(InvalidIpException::class);

        Ip::create('test');
    }

    /**
     * @test
     * @return Ip
     * @throws InvalidIpException
     * @throws Exception
     */
    public function it_should_return_an_ip_object_when_correct_data_given()
    {
        $ip = Ip::create('127.0.0.1');

        $this->assertInstanceOf(Ip::class, $ip);

        return $ip;
    }

    /**
     * @test
     * @depends it_should_return_an_ip_object_when_correct_data_given
     * @param Ip $ip Ip
     * @return void
     */
    public function it_should_contain_correct_ip(Ip $ip)
    {
        $this->assertEquals('127.0.0.1', (string) $ip);
    }
}
