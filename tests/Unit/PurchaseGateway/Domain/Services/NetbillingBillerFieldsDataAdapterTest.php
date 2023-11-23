<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\NetbillingBillerFieldsDataAdapter;
use Tests\UnitTestCase;

class NetbillingBillerFieldsDataAdapterTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $billerFields;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->billerFields = [
            'accountId'        => '1234',
            'siteTag'          => 'MGSITE',
            'binRouting'       => 'INT\/PX#100XTxEP',
            'merchantPassword' => 'Bt3XfffffddddgMRo8'
        ];
    }

    /**
     * @test
     * @return BillerFields
     * @throws Exception
     */
    public function it_should_return_a_netbilling_biller_fields_object(): BillerFields
    {
        $billerFieldsDataAdapter = new NetbillingBillerFieldsDataAdapter();

        $netbillingBillerFields = $billerFieldsDataAdapter->convert($this->billerFields);
        $this->assertInstanceOf(NetbillingBillerFields::class, $netbillingBillerFields);
        return $netbillingBillerFields;
    }

    /**
     * @test
     * @depends it_should_return_a_netbilling_biller_fields_object
     * @param NetbillingBillerFields $netbillingBillerFields biller fields
     * @return void
     */
    public function converted_object_should_have_bin_routing_code(NetbillingBillerFields $netbillingBillerFields): void
    {
        $this->assertEquals($netbillingBillerFields->binRouting(), $this->billerFields['binRouting']);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_throw_an_exception_if_invalid_data_provided(): void
    {
        $billerFields            = [
            'wrongIndex' => '1234'
        ];
        $billerFieldsDataAdapter = new NetbillingBillerFieldsDataAdapter();
        $this->expectException(Exception::class);
        $billerFieldsDataAdapter->convert($billerFields);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_return_a_biller_fields_object_with_optional_bin_routing(): void
    {
        $billerFieldsDataAdapter = new NetbillingBillerFieldsDataAdapter();

        $netbillingBillerFields = $billerFieldsDataAdapter->convert(
            [
                'accountId' => $this->billerFields['accountId'],
                'siteTag'   => $this->billerFields['siteTag']
            ]
        );
        $this->assertInstanceOf(NetbillingBillerFields::class, $netbillingBillerFields);
    }
}
