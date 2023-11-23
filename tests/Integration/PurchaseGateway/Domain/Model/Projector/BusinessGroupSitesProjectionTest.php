<?php

namespace Tests\Integration\PurchaseGateway\Domain\Model\Projector;

use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjection;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroupSite;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBusinessGroupProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;
use Tests\IntegrationTestCase;

class BusinessGroupSitesProjectionTest extends IntegrationTestCase
{
    /**
     * @var DoctrineBusinessGroupProjectionRepository
     */
    private $doctrineBusinessGroupProjectionRepository;

    /**
     * @var DoctrineSiteProjectionRepository
     */
    private $doctrineSiteProjectionRepository;

    /**
     * @var BusinessGroupSitesProjection
     */
    private $businessGroupSitesProjection;

    /**
     * @var ServiceCollection
     */
    private $serviceCollection;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->doctrineBusinessGroupProjectionRepository = new DoctrineBusinessGroupProjectionRepository(
            app('em'),
            app('em')->getClassMetadata(BusinessGroup::class)
        );

        $this->doctrineSiteProjectionRepository = new DoctrineSiteProjectionRepository(
            app('em'),
            app('em')->getClassMetadata(Site::class)
        );

        $this->businessGroupSitesProjection = new BusinessGroupSitesProjection(
            $this->doctrineBusinessGroupProjectionRepository,
            $this->doctrineSiteProjectionRepository
        );

        $this->serviceCollection = new ServiceCollection();
        $this->serviceCollection->add(
            Service::create('Service name', true)
        );
    }

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function it_should_add_business_group_when_business_group_created_is_triggered(): string
    {
        $businessGroup = BusinessGroup::create(
            $this->faker->uuid,
            $this->faker->uuid,
            [
                [
                    'key'       => $this->faker->uuid,
                    'createdAt' => ($this->faker->dateTime)->format('Y-m-d H:i:s')
                ]
            ],
            'descriptor'
        );

        $this->businessGroupSitesProjection->whenBusinessGroupCreated($businessGroup);

        app('em')->flush();

        $retrievedBusinessGroup = $this->doctrineBusinessGroupProjectionRepository->find($businessGroup->id());

        $this->assertEquals($businessGroup, $retrievedBusinessGroup);

        return $businessGroup->id();
    }

    /**
     * @test
     * @depends it_should_add_business_group_when_business_group_created_is_triggered
     * @param string $businessGroupId Business group id
     * @return array
     * @throws \Exception
     */
    public function it_should_add_site_when_site_created_is_triggered(string $businessGroupId): array
    {
        $site = BusinessGroupSite::create(
            $this->faker->uuid,
            $businessGroupId,
            $this->faker->url,
            $this->faker->name,
            $this->faker->phoneNumber,
            $this->faker->phoneNumber,
            $this->faker->url,
            $this->faker->email,
            $this->faker->url,
            $this->faker->url,
            $this->faker->url,
            $this->serviceCollection->toArray(),
            true,
            false
        );

        $this->businessGroupSitesProjection->whenSiteCreated($site);

        app('em')->flush();

        $retrievedSite = $this->doctrineSiteProjectionRepository->findSiteById($site->id());

        $this->assertSame($site->id(), $retrievedSite->id());

        return [
            'site'          => $site,
            'retrievedSite' => $retrievedSite
        ];
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_business_group_flag_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->businessGroupId(),
            (string) $addBusinessGroupSiteResult['retrievedSite']->businessGroupId()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_url_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->url(),
            $addBusinessGroupSiteResult['retrievedSite']->url()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_name_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->name(),
            $addBusinessGroupSiteResult['retrievedSite']->name()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_phone_number_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->phoneNumber(),
            $addBusinessGroupSiteResult['retrievedSite']->phoneNumber()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_skype_number_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->skypeNumber(),
            $addBusinessGroupSiteResult['retrievedSite']->skypeNumber()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_support_link_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->supportLink(),
            $addBusinessGroupSiteResult['retrievedSite']->supportLink()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_mail_support_link_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->mailSupportLink(),
            $addBusinessGroupSiteResult['retrievedSite']->mailSupportLink()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_message_support_link_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->messageSupportLink(),
            $addBusinessGroupSiteResult['retrievedSite']->messageSupportLink()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_message_cancellation_link_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->cancellationLink(),
            $addBusinessGroupSiteResult['retrievedSite']->cancellationLink()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_postback_url_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->postbackUrl(),
            $addBusinessGroupSiteResult['retrievedSite']->postbackUrl()
        );
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return void
     */
    public function it_should_have_correct_service_collection_when_site_was_added_successfully(
        array $addBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $addBusinessGroupSiteResult['site']->serviceCollection(),
            $addBusinessGroupSiteResult['retrievedSite']->serviceCollection()->toArray()
        );
    }

    /**
     * @test
     * @depends it_should_add_business_group_when_business_group_created_is_triggered
     * @param string $businessGroupId Business group id
     * @return string
     * @throws \Exception
     */
    public function it_should_update_business_group_when_business_group_updated_is_triggered(
        string $businessGroupId
    ): string {
        $businessGroup = BusinessGroup::create(
            $businessGroupId,
            $this->faker->uuid,
            [
                [
                    'key'       => $this->faker->uuid,
                    'createdAt' => ($this->faker->dateTime)->format('Y-m-d H:i:s')
                ]
            ],
            'newDescriptor'
        );

        $this->businessGroupSitesProjection->whenBusinessGroupUpdated($businessGroup);

        app('em')->flush();

        $retrievedBusinessGroup = $this->doctrineBusinessGroupProjectionRepository->find($businessGroup->id());

        $this->assertEquals($businessGroup, $retrievedBusinessGroup);

        return $businessGroup->id();
    }

    /**
     * @test
     * @depends it_should_add_site_when_site_created_is_triggered
     * @param array $addBusinessGroupSiteResult Result of business group add
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function it_should_update_site_when_site_update_is_triggered(array $addBusinessGroupSiteResult): array
    {
        $site = BusinessGroupSite::create(
            $addBusinessGroupSiteResult['retrievedSite']->id(),
            (string) $addBusinessGroupSiteResult['retrievedSite']->businessGroupId(),
            $this->faker->url,
            $this->faker->name,
            $this->faker->phoneNumber,
            $this->faker->phoneNumber,
            $this->faker->url,
            $this->faker->email,
            $this->faker->url,
            $this->faker->url,
            $this->faker->url,
            $this->serviceCollection->toArray(),
            true,
            false
        );

        $this->businessGroupSitesProjection->whenSiteUpdated($site);

        app('em')->flush();

        $retrievedSite = $this->doctrineSiteProjectionRepository->findSiteById($site->id());

        $this->assertSame($site->id(), $retrievedSite->id());

        return [
            'site'          => $site,
            'retrievedSite' => $retrievedSite
        ];
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_business_group_flag_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->businessGroupId(),
            (string) $updateBusinessGroupSiteResult['retrievedSite']->businessGroupId()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_url_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->url(),
            $updateBusinessGroupSiteResult['retrievedSite']->url()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_name_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->name(),
            $updateBusinessGroupSiteResult['retrievedSite']->name()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_phone_number_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->phoneNumber(),
            $updateBusinessGroupSiteResult['retrievedSite']->phoneNumber()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_skype_number_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->skypeNumber(),
            $updateBusinessGroupSiteResult['retrievedSite']->skypeNumber()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_support_link_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->supportLink(),
            $updateBusinessGroupSiteResult['retrievedSite']->supportLink()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_mail_support_link_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->mailSupportLink(),
            $updateBusinessGroupSiteResult['retrievedSite']->mailSupportLink()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_message_support_link_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->messageSupportLink(),
            $updateBusinessGroupSiteResult['retrievedSite']->messageSupportLink()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_postback_url_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->postbackUrl(),
            $updateBusinessGroupSiteResult['retrievedSite']->postbackUrl()
        );
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     */
    public function it_should_have_correct_service_collection_when_site_was_updated_successfully(
        array $updateBusinessGroupSiteResult
    ): void {
        $this->assertSame(
            $updateBusinessGroupSiteResult['site']->serviceCollection(),
            $updateBusinessGroupSiteResult['retrievedSite']->serviceCollection()->toArray()
        );
    }

    /**
     * @test
     * @depends it_should_update_business_group_when_business_group_updated_is_triggered
     * @param string $businessGroupId Business group id
     * @return void
     */
    public function it_should_delete_business_group_when_business_group_deleted_is_triggered(string $businessGroupId): void
    {
        $businessGroup = BusinessGroup::create(
            $businessGroupId,
            $this->faker->uuid,
            [
                [
                    'key'       => $this->faker->uuid,
                    'createdAt' => ($this->faker->dateTime)->format('Y-m-d H:i:s')
                ]
            ],
            'descriptor'
        );

        $this->businessGroupSitesProjection->whenBusinessGroupDeleted($businessGroup);

        app('em')->flush();

        $retrievedBusinessGroup = $this->doctrineBusinessGroupProjectionRepository->find($businessGroup->id());

        $this->assertEmpty($retrievedBusinessGroup);
    }

    /**
     * @test
     * @depends it_should_update_site_when_site_update_is_triggered
     * @param array $updateBusinessGroupSiteResult Result of business group update
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function it_should_delete_site_when_site_deleted_is_triggered(array $updateBusinessGroupSiteResult): void
    {
        $site = BusinessGroupSite::create(
            $updateBusinessGroupSiteResult['retrievedSite']->id(),
            (string) $updateBusinessGroupSiteResult['retrievedSite']->businessGroupId(),
            $this->faker->url,
            $this->faker->name,
            $this->faker->phoneNumber,
            $this->faker->phoneNumber,
            $this->faker->url,
            $this->faker->email,
            $this->faker->url,
            $this->faker->url,
            $this->faker->url,
            $this->serviceCollection->toArray(),
            true,
            false
        );

        $this->businessGroupSitesProjection->whenSiteDeleted($site);

        app('em')->flush();

        $retrievedSite = $this->doctrineSiteProjectionRepository->findSiteById($site->id());

        $this->assertEmpty($retrievedSite);
    }
}
