<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionMember;
use Tests\UnitTestCase;

class MemberInformationTest extends UnitTestCase
{
    /**
     * @test
     * @return MemberInformation
     */
    public function it_should_return_a_member_information_object_if_correct_data_is_sent()
    {
        $retrieveTransactionMember = $this->createMock(RetrieveTransactionMember::class);
        $retrieveTransactionMember->method('getEmail')->willReturn('email@email.com');
        $retrieveTransactionMember->method('getPhoneNumber')->willReturn('123456');
        $retrieveTransactionMember->method('getFirstName')->willReturn('Gigel');
        $retrieveTransactionMember->method('getLastName')->willReturn('Dorel');
        $retrieveTransactionMember->method('getAddress')->willReturn('Address');
        $retrieveTransactionMember->method('getCity')->willReturn('Galati');
        $retrieveTransactionMember->method('getState')->willReturn('Galati');
        $retrieveTransactionMember->method('getZip')->willReturn('800087');
        $retrieveTransactionMember->method('getCountry')->willReturn('Romania');

        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getMember')->willReturn($retrieveTransactionMember);

        $memberInformation = new MemberInformation($retrieveTransaction);
        $this->assertInstanceOf(MemberInformation::class, $memberInformation);

        return $memberInformation;
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_an_email(MemberInformation $memberInformation)
    {
        $this->assertEquals('email@email.com', $memberInformation->email());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_phone_number(MemberInformation $memberInformation)
    {
        $this->assertEquals('123456', $memberInformation->phoneNumber());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_first_name(MemberInformation $memberInformation)
    {
        $this->assertEquals('Gigel', $memberInformation->firstName());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_last_name(MemberInformation $memberInformation)
    {
        $this->assertEquals('Dorel', $memberInformation->lastName());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_address(MemberInformation $memberInformation)
    {
        $this->assertEquals('Address', $memberInformation->address());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_city(MemberInformation $memberInformation)
    {
        $this->assertEquals('Galati', $memberInformation->city());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_state(MemberInformation $memberInformation)
    {
        $this->assertEquals('Galati', $memberInformation->state());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_zip_code(MemberInformation $memberInformation)
    {
        $this->assertEquals('800087', $memberInformation->zip());
    }

    /**
     * @test
     * @param MemberInformation $memberInformation MemberInformation
     * @depends it_should_return_a_member_information_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_country(MemberInformation $memberInformation)
    {
        $this->assertEquals('Romania', $memberInformation->country());
    }
}
