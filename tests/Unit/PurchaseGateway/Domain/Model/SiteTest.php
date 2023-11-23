<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use Tests\UnitTestCase;

class SiteTest extends UnitTestCase
{
    /**
     * @test
     * @return Site
     * @throws \Exception
     */
    public function it_should_return_a_site_when_correct_data_is_sent(): Site
    {
        $site = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $this->createServiceCollection(),
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $this->createPublicKeyCollection(),
            'Business group descriptor',
            false,
            false,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->assertInstanceOf(Site::class, $site);

        return $site;
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_as_array(Site $site): void
    {
        $this->assertIsArray($site->toArray());
    }

    /**
 * @test
 * @depends it_should_return_a_site_when_correct_data_is_sent
 * @param Site $site Site
 * @return void
 */
    public function it_should_return_site_data_with_correct_site_id(Site $site): void
    {
        $this->assertSame('86b95cd0-78ad-4052-9e75-5991c15d6ffa', $site->toArray()['siteId']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_business_group_id(Site $site): void
    {
        $this->assertSame(
            '86b95cd0-78ad-4052-9e75-5991c15d6ffa',
            $site->toArray()['businessGroupId']
        );
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_url(Site $site): void
    {
        $this->assertSame('http://www.brazzers.com', $site->toArray()['url']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_name(Site $site): void
    {
        $this->assertSame('Brazzers', $site->toArray()['name']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_phone_number(Site $site): void
    {
        $this->assertSame('1111-1111-1111', $site->toArray()['phoneNumber']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_skype_number(Site $site): void
    {
        $this->assertSame('2222-2222-2222', $site->toArray()['skypeNumber']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_support_link(Site $site): void
    {
        $this->assertSame('http://localhost/supportLink', $site->toArray()['supportLink']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_mail_support_link(Site $site): void
    {
        $this->assertSame('mail@support.com', $site->toArray()['mailSupportLink']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_message_support_link(Site $site): void
    {
        $this->assertSame('http://localhost/messageSupportLink', $site->toArray()['messageSupportLink']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_postback_url(Site $site): void
    {
        $this->assertSame('http://localhost/postbackUrl', $site->toArray()['postbackUrl']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site
     * @return void
     */
    public function it_should_return_site_date_with_correct_isStickyGateway(Site $site): void
    {
        $this->assertSame(false,$site->toArray()['isStickyGateway']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_service_collection(Site $site): void
    {
        $this->assertSame(
            [
               [
                    "name" => "Service name",
                    "enabled" => true
               ]
            ],
            $site->toArray()['serviceCollection']
        );
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_private_key(Site $site): void
    {
        $this->assertSame('ab3708dc-1415-4654-9403-a4108999a80a', $site->toArray()['privateKey']);
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_public_key_collection(Site $site): void
    {
        $this->assertArrayHasKey('publicKeyCollection', $site->toArray());
    }

    /**
     * @test
     * @depends it_should_return_a_site_when_correct_data_is_sent
     * @param Site $site Site
     * @return void
     */
    public function it_should_return_site_data_with_correct_descriptor(Site $site): void
    {
        $this->assertSame('Business group descriptor', $site->toArray()['descriptor']);
    }

    /**
     * @return ServiceCollection
     */
    private function createServiceCollection(): ServiceCollection
    {
        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(
            Service::create('Service name', true)
        );

        return $serviceCollection;
    }


    /**
     * @return PublicKeyCollection
     * @throws \Exception
     */
    private function createPublicKeyCollection(): PublicKeyCollection
    {
        $publicKeyCollection = new PublicKeyCollection();

        $publicKeyCollection->add(
            PublicKey::create(
                KeyId::createFromString('3dcc4a19-e2a8-4622-8e03-52247bbd302d'),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2019-11-15 16:11:41.0000')
            )
        );

        return $publicKeyCollection;
    }


    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_default_attempts_when_0_is_passed(): void
    {
        $site = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $this->createServiceCollection(),
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $this->createPublicKeyCollection(),
            'Business group descriptor',
            false,
            false,
            0
        );

        $this->assertEquals(Site::DEFAULT_NUMBER_OF_ATTEMPTS, $site->attempts());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_1_attempts_when_1_is_passed(): void
    {
        $site = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $this->createServiceCollection(),
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $this->createPublicKeyCollection(),
            'Business group descriptor',
            false,
            false,
            1
        );

        $this->assertEquals(1, $site->attempts());
    }


    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_default_attempts_when_negative_number_is_passed(): void
    {
        $site = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $this->createServiceCollection(),
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $this->createPublicKeyCollection(),
            'Business group descriptor',
            false,
            false,
            -1
        );

        $this->assertEquals(Site::DEFAULT_NUMBER_OF_ATTEMPTS, $site->attempts());
    }
}
