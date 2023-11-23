<?php

declare(strict_types=1);

namespace PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBillerFields;
use Tests\UnitTestCase;

class QyssoBillerFieldsTest extends UnitTestCase
{
    /**
     * @test
     * @return QyssoBillerFields
     */
    public function it_should_return_an_qysso_biller_fields_object(): QyssoBillerFields
    {
        $result = QyssoBillerFields::create('companyNum', 'hashkey1');

        self::assertInstanceOf(QyssoBillerFields::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_an_qysso_biller_fields_object
     * @param QyssoBillerFields $billerFields
     */
    public function it_should_contain_the_correct_company_number(QyssoBillerFields $billerFields): void
    {
        self::assertSame('companyNum', $billerFields->companyNum());
    }

    /**
     * @test
     * @depends it_should_return_an_qysso_biller_fields_object
     * @param QyssoBillerFields $billerFields
     */
    public function it_should_contain_the_correct_personal_hash_key(QyssoBillerFields $billerFields): void
    {
        self::assertSame('hashkey1', $billerFields->personalHashKey());
    }

    /**
     * @test
     * @depends it_should_return_an_qysso_biller_fields_object
     * @param QyssoBillerFields $billerFields
     */
    public function it_should_return_postback_id(QyssoBillerFields $billerFields): void
    {
        self::assertSame(QyssoBillerFields::POSTBACK_ID, $billerFields->postbackId());
    }
}
