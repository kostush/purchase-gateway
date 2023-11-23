<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;

class SiteServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $em = $this->app['registry']->getManager('mysql-readonly');

        $this->app->bind(
            SiteRepositoryReadOnly::class,
            function () use ($em) {
                return new DoctrineSiteProjectionRepository(
                    $em,
                    $em->getClassMetadata(Site::class)
                );
            }
        );
    }
}