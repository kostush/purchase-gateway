<?php

declare(strict_types=1);

namespace App\Providers;

use Google\Cloud\PubSub\PubSubClient;
use Illuminate\Support\ServiceProvider;
use ProBillerNG\PurchaseGateway\Domain\Model\Publisher;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\GooglePubSub\GooglePublisher;

class PublisherProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            Publisher::class,
            function () {
                return new GooglePublisher(
                    new PubSubClient(
                        [
                            'projectId' => env('GOOGLE_CLOUD_PROJECT', 'mg-probiller-dev')
                        ]
                    ),
                    env('GCLOUD_PUB_SUB_TOPIC_ID','mg-void-legacy')
                );
            }
        );
    }
}
