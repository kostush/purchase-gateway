<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\UnknownBiller;
use Tests\UnitTestCase;

class PaymentTemplateTest extends UnitTestCase
{
    private const TEMPLATE_ID      = '4c22fba2-f883-11e8-8eb2-f2801f1b9fff';
    private const FIRST_SIX        = '123456';
    private const LAST_FOUR        = '1234';
    private const IS_SAFE          = false;
    private const EXPIRATION_YEAR  = '2019';
    private const EXPIRATION_MONTH = '11';
    private const LAST_USED_DATE   = '2019-08-11 15:15:25';
    private const CREATED_AT       = '2019-08-11 15:15:25';
    private const BILLER_NAME      = 'rocketgate';
    private const BILLER_FIELDS    = [];

    /**
     * @test
     * @return PaymentTemplate
     */
    public function it_should_return_a_payment_template_when_created(): PaymentTemplate
    {
        $result = PaymentTemplate::create(
            self::TEMPLATE_ID,
            self::FIRST_SIX,
            self::LAST_FOUR,
            self::EXPIRATION_YEAR,
            self::EXPIRATION_MONTH,
            self::LAST_USED_DATE,
            self::CREATED_AT,
            self::BILLER_NAME,
            self::BILLER_FIELDS
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_template_id_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::TEMPLATE_ID, $paymentTemplate->templateId());
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_first_six_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::FIRST_SIX, $paymentTemplate->firstSix());
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_is_safe_flag_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::IS_SAFE, $paymentTemplate->isSafe());
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_expiration_year_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::EXPIRATION_YEAR, $paymentTemplate->expirationYear());
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_expiration_month_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::EXPIRATION_MONTH, $paymentTemplate->expirationMonth());
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_last_used_date_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::LAST_USED_DATE, $paymentTemplate->lastUsedDate());
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_when_created
     * @param PaymentTemplate $paymentTemplate Payment template
     * @return void
     */
    public function it_should_contain_the_biller_name_when_created(PaymentTemplate $paymentTemplate): void
    {
        $this->assertSame(self::BILLER_NAME, $paymentTemplate->billerName());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_not_contain_biller_name_in_array_when_created_with_blank_biller_name(): void
    {

        $paymentTemplate = PaymentTemplate::create(
            self::TEMPLATE_ID,
            self::FIRST_SIX,
            self::LAST_FOUR,
            self::EXPIRATION_YEAR,
            self::EXPIRATION_MONTH,
            self::LAST_USED_DATE,
            self::CREATED_AT,
            '',
            self::BILLER_FIELDS
        );

        $this->assertInstanceOf(PaymentTemplate::class, $paymentTemplate);

        $paymentTemplateArray = $paymentTemplate->toArray();

        $this->assertArrayNotHasKey('billerName', $paymentTemplateArray);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_not_contain_biller_name_in_array_when_created_with_unknown_biller(): void
    {

        $paymentTemplate = PaymentTemplate::create(
            self::TEMPLATE_ID,
            self::FIRST_SIX,
            self::LAST_FOUR,
            self::EXPIRATION_YEAR,
            self::EXPIRATION_MONTH,
            self::LAST_USED_DATE,
            self::CREATED_AT,
            UnknownBiller::BILLER_NAME,
            self::BILLER_FIELDS
        );

        $this->assertInstanceOf(PaymentTemplate::class, $paymentTemplate);

        $paymentTemplateArray = $paymentTemplate->toArray();

        $this->assertArrayNotHasKey('billerName', $paymentTemplateArray);
    }
}
