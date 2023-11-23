<?php
declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;

class BundleServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $em = $this->app['registry']->getManager('mysql-readonly');

        $this->app->bind(
            BundleRepositoryReadOnly::class,
            function () use ($em) {
                return new DoctrineBundleProjectionRepository(
                    $em,
                    $em->getClassMetadata(Bundle::class)
                );
            }
        );
    }
}
