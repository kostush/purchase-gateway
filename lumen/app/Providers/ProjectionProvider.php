<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application;
use ProBillerNG\Projection\Domain\ProjectorBuilder;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\Addon;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\AddonRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BundleRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BusinessGroupRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineAddonProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBusinessGroupProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;

class ProjectionProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {

        $this->app->bind(
            BundleRepository::class,
            function (Application $app) {
                return new DoctrineBundleProjectionRepository(
                    $app['em'],
                    $app['em']->getClassMetadata(Bundle::class)
                );
            }
        );

        $this->app->bind(
            AddonRepository::class,
            function (Application $app) {
                return new DoctrineAddonProjectionRepository(
                    $app['em'],
                    $app['em']->getClassMetadata(Addon::class)
                );
            }
        );

        $this->app->bind(
            SiteRepository::class,
            function (Application $app) {
                return new DoctrineSiteProjectionRepository(
                    $app['em'],
                    $app['em']->getClassMetadata(Site::class)
                );
            }
        );

        $this->app->bind(
            BusinessGroupRepository::class,
            function (Application $app) {
                return new DoctrineBusinessGroupProjectionRepository(
                    $app['em'],
                    $app['em']->getClassMetadata(BusinessGroup::class)
                );
            }
        );

        $this->app->singleton(
            ProjectorBuilder::class,
            function (Application $app) {
                $projectorBuilder = new ProjectorBuilder();

                $projectorBuilder->registerProjector(
                    BundleAddonsProjector::WORKER_NAME,
                    $app->make(BundleAddonsProjector::class)
                );

                $projectorBuilder->registerProjector(
                    BusinessGroupSitesProjector::WORKER_NAME,
                    $app->make(BusinessGroupSitesProjector::class)
                );

                return $projectorBuilder;
            }
        );
    }
}
