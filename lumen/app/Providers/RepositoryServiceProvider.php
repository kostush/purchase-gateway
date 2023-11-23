<?php
declare(strict_types=1);

namespace App\Providers;

use Doctrine\ORM\EntityManager;
use Laravel\Lumen\Application;
use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Model\DoctrinePurchaseRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use Redis;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Repository
        $this->app->bind(
            PurchaseRepository::class,
            function (Application $application) {
                /** @var EntityManager $em */
                $em = $application['em'];

                return new DoctrinePurchaseRepository(
                    $em,
                    $em->getClassMetaData(Purchase::class)
                );
            }
        );

        // Redis
        $this->app->singleton(
            Redis::class,
            function () {
                $redis = new Redis();
                $redis->connect(
                    env('REDIS_HOST'),
                    (int) env('REDIS_PORT')
                );

                $redis->auth(env('REDIS_PASS'));
                $redis->setOption(REDIS::OPT_PREFIX, (string) env('REDIS_PREFIX'));

                return $redis;
            }
        );

        $this->app->bind(
            InMemoryRepository::class,
            RedisRepository::class
        );
    }
}
