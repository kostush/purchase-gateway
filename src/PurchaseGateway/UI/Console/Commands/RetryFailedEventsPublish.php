<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\RetryFailedPurchaseProcessedPublishingCommandHandler;

class RetryFailedEventsPublish extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ng:failed-event-publish:retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry publishing of events for which publishing failed initially';

    /**
     * @var RetryFailedPurchaseProcessedPublishingCommandHandler
     */
    private $handler;

    /**
     * RetryFailedEventsPublish constructor.
     * @param RetryFailedPurchaseProcessedPublishingCommandHandler $handler Retry Failed Purchase Processed Handler.
     */
    public function __construct(RetryFailedPurchaseProcessedPublishingCommandHandler $handler)
    {
        $this->handler = $handler;

        parent::__construct();
    }

    /**
     * @throws LoggerException
     * @throws Exception
     * @return void
     */
    public function handle(): void
    {
        try {
            $this->handler->execute();
        } catch (\Throwable $exception) {
            Log::error('Failed Event Publish Retry worker encountered errors');
            Log::logException($exception);
        }

        // Sleep to not cause issues with supervisor
        sleep(5);
    }
}
