<?php

namespace ProBillerNG\PurchaseGateway\UI\Console\Commands;

use Illuminate\Console\Command;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventCommandHandler;
use ProBillerNG\ServiceBus\ServiceBus;

class PurchaseToMemberProfileEnrichedEventConsumer extends Command
{
    /**
     * @var string
     */
    protected $signature = 'ng:message:consumer-purchase-to-member-profile {queueToConsume}';

    /**
     * @var string
     */
    protected $description = 'Consume Messages for Purchase Processed and create Member Profile Enriched Event';

    /**
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    public function handle(): void
    {
        $serviceBus = app()->makeWith(
            ServiceBus::class,
            [
                'queueToConsume' => (int) $this->argument('queueToConsume'),
                'consumerName'   => CreateMemberProfileEnrichedEventCommandHandler::WORKER_NAME,
            ]
        );

        try {
            Log::info('Consuming started');
            $serviceBus->consume();
            Log::info('Consuming finished');
        } catch (\Throwable $exception) {
            Log::error(
                'Error encountered while consuming events',
                ['consumer' => __CLASS__]
            );
            Log::logException($exception);
        }
    }
}
