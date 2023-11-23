<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector;

use ProBillerNG\Projection\Domain\Projection;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroupSite;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BusinessGroupRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;

class BusinessGroupSitesProjection extends Projection
{
    /** @var BusinessGroupRepository */
    protected $businessGroupRepository;

    /** @var SiteRepository */
    protected $siteRepository;

    /**
     * BusinessGroupSitesProjection constructor.
     * @param BusinessGroupRepository $businessGroupRepository Business Group Repository
     * @param SiteRepository          $siteRepository          Site Repository
     */
    public function __construct(BusinessGroupRepository $businessGroupRepository, SiteRepository $siteRepository)
    {
        $this->businessGroupRepository = $businessGroupRepository;
        $this->siteRepository          = $siteRepository;
    }

    /**
     * @param BusinessGroup $item Business Group
     * @return void
     */
    public function whenBusinessGroupCreated(BusinessGroup $item): void
    {
        $this->createBusinessGroups($item);
    }

    /**
     * @param BusinessGroup $item Business Group
     *
     * @return void
     * @throws \Exception
     */
    public function whenBusinessGroupUpdated(BusinessGroup $item): void
    {
        $this->updateBusinessGroups($item);
    }

    /**
     * @param BusinessGroup $item Business Group
     * @return void
     */
    public function whenBusinessGroupDeleted(BusinessGroup $item): void
    {
        $this->deleteBusinessGroups($item);
    }

    /**
     * @param BusinessGroupSite $item Business group site
     * @return void
     * @throws \Exception
     */
    public function whenSiteCreated(BusinessGroupSite $item): void
    {
        $this->createSite($item);
    }

    /**
     * @param BusinessGroupSite $item Business group site
     * @return void
     * @throws \Exception
     */
    public function whenSiteUpdated(BusinessGroupSite $item): void
    {
        $this->updateSite($item);
    }

    /**
     * @param BusinessGroupSite $item Business group site
     * @return void
     * @throws \Exception
     */
    public function whenSiteDeleted(BusinessGroupSite $item): void
    {
        $this->deleteSite($item);
    }

    /**
     * @param BusinessGroup $item Business group site
     * @return void
     */
    private function createBusinessGroups(BusinessGroup $item): void
    {
        $this->businessGroupRepository->add($item);
    }

    /**
     * @param BusinessGroup $item Business group
     *
     * @return void
     * @throws \Exception
     */
    private function updateBusinessGroups(BusinessGroup $item): void
    {
        $this->businessGroupRepository->update($item);

        /**
         * @var Site[]
         */
        $sites = $this->siteRepository->findSitesByBusinessGroupId($item->id());

        if (count($sites) == 0) {
            return;
        }

        array_map(
            function (Site $site) use ($item) {
                $site->updateSiteWithBusinessGroupInfo(
                    $item->descriptor(),
                    $item->privateKey(),
                    $this->createPublicKeyCollection($item->publicKeyCollection())
                );

                $this->siteRepository->update($site);
            },
            $sites
        );
    }

    /**
     * @param BusinessGroup $item Business group
     * @return void
     */
    private function deleteBusinessGroups(BusinessGroup $item): void
    {
        $businessGroup = $this->businessGroupRepository->findBusinessGroupById($item->id());
        $this->businessGroupRepository->delete($businessGroup);
    }

    /**
     * @param BusinessGroupSite $item Business group site
     * @return void
     * @throws \Exception
     */
    private function createSite(BusinessGroupSite $item): void
    {
        $businessGroup = $this->businessGroupRepository->findBusinessGroupById($item->businessGroupId());
        $site          = Site::create(
            SiteId::createFromString($item->id()),
            BusinessGroupId::createFromString($businessGroup->id()),
            $item->url(),
            $item->name(),
            $item->phoneNumber(),
            $item->skypeNumber(),
            $item->supportLink(),
            $item->mailSupportLink(),
            $item->messageSupportLink(),
            $item->cancellationLink(),
            $item->postbackUrl(),
            $this->createServiceCollection($item->serviceCollection()),
            $businessGroup->privateKey(),
            $this->createPublicKeyCollection($businessGroup->publicKeyCollection()),
            $businessGroup->descriptor(),
            $item->isStickyGateway(),
            $item->isNsfSupported(),
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->siteRepository->add($site);
    }

    /**
     * @param BusinessGroupSite $item Business group site
     * @return void
     * @throws \Exception
     */
    private function updateSite(BusinessGroupSite $item): void
    {
        $site = $this->siteRepository->findSiteById($item->id());
        $this->siteRepository->delete($site);
        $this->createSite($item);
    }

    /**
     * @param BusinessGroupSite $item Business group site
     * @return void
     */
    private function deleteSite(BusinessGroupSite $item): void
    {
        $site = $this->siteRepository->findSiteById($item->id());
        $this->siteRepository->delete($site);
    }

    /**
     * Call the repository to delete actually reset the projection.
     * @return void
     */
    public function whenProjectionDeleted(): void
    {
        $this->siteRepository->deleteProjection();
        $this->businessGroupRepository->deleteProjection();
    }

    /**
     * Call the repository to reset the projection.
     * @return void
     */
    public function whenProjectionReset(): void
    {
        $this->siteRepository->resetProjection();
        $this->businessGroupRepository->resetProjection();
    }

    /**
     * @param array $services Services
     * @return ServiceCollection
     */
    private function createServiceCollection(?array $services): ServiceCollection
    {
        $serviceCollection = new ServiceCollection();

        if (!empty($services)) {
            foreach ($services as $key => $service) {
                if (empty($service)) {
                    break;
                }

                $newService = Service::create(
                    $service['name'],
                    $service['enabled'],
                    $service['options'] ?? []
                );

                $serviceCollection->add($newService);
            }
        }

        return $serviceCollection;
    }

    /**
     * @param array $publicKeys Public keys
     * @return PublicKeyCollection
     * @throws \Exception
     */
    private function createPublicKeyCollection(array $publicKeys): PublicKeyCollection
    {
        $publicKeyCollection = new PublicKeyCollection();

        if (empty($publicKeys)) {
            return $publicKeyCollection;
        }

        foreach ($publicKeys as $key => $publicKey) {
            $publicKeyCollection->add(
                PublicKey::create(
                    KeyId::createFromString($publicKey['key']),
                    \DateTimeImmutable::createFromMutable(
                        new \DateTime($publicKey['createdAt'])
                    )
                )
            );
        }

        return $publicKeyCollection;
    }
}
