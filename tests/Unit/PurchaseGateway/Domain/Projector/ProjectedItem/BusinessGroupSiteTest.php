<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ProjectedItem;

use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroupSite;
use Tests\UnitTestCase;

class BusinessGroupSiteTest extends UnitTestCase
{
    /**
     * @test
     * @return BusinessGroupSite
     */
    public function it_should_return_business_group_site_projection(): BusinessGroupSite
    {
        $businessGroupSite = BusinessGroupSite::create(
            $this->faker->uuid,
            $this->faker->uuid,
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            '',
            '',
            '',
            'https://localhost/cancellationLink',
            '',
            [
                'name' => 'a service',
                'enabled' => true
            ],
            true,
            false
        );

        $this->assertInstanceOf(BusinessGroupSite::class, $businessGroupSite);

        return $businessGroupSite;
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_site_id_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('siteId', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_business_group_id_key(
        BusinessGroupSite $businessGroupSite
    ): void {
        $this->assertArrayHasKey('businessGroupId', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_url_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('url', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_name_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('name', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_phone_number_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('phoneNumber', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_skype_number_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('skypeNumber', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_support_link_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('supportLink', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_mail_support_link_key(
        BusinessGroupSite $businessGroupSite
    ): void {
        $this->assertArrayHasKey('mailSupportLink', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_message_support_link_key(
        BusinessGroupSite $businessGroupSite
    ): void {
        $this->assertArrayHasKey('messageSupportLink', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_cancellation_link_key(
        BusinessGroupSite $businessGroupSite
    ): void {
        $this->assertArrayHasKey('cancellationLink', $businessGroupSite->toArray());
        $this->assertEquals('https://localhost/cancellationLink', $businessGroupSite->toArray()['cancellationLink']);
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_postback_url_key(BusinessGroupSite $businessGroupSite): void
    {
        $this->assertArrayHasKey('postbackUrl', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_service_collection_key(
        BusinessGroupSite $businessGroupSite
    ): void {
        $this->assertArrayHasKey('serviceCollection', $businessGroupSite->toArray());
    }

    /**
     * @test
     * @depends it_should_return_business_group_site_projection
     * @param BusinessGroupSite $businessGroupSite Business group site
     * @return void
     */
    public function it_should_return_a_business_group_site_with_is_nsf_supported_key(
        BusinessGroupSite $businessGroupSite
    ): void {
        $this->assertArrayHasKey('isNsfSupported', $businessGroupSite->toArray());
    }
}
