<?php

use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventCommandHandler;
use ProBillerNG\Projection\Domain\ConfigRetriever;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjector;
use  ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases\TimerPendingPurchasesCommandHandler;

return [
    // Default settings
    ConfigRetriever::DEFAULT_CONFIG_NAME               => [
        // Time for a worker / projector to sleep when no more events are found
        ConfigRetriever::WORKER_SLEEP_TIME => 5, // seconds
    ],
    // Config per worker / projector. Same name as one used in command must be used
    CreateLegacyImportEventCommandHandler::WORKER_NAME => [
        // Time for a worker / projector to sleep when no more events are found
        ConfigRetriever::WORKER_SLEEP_TIME => 1, // seconds
    ],
    BundleAddonsProjector::WORKER_NAME                 => [
        // Time in seconds for a worker / projector to sleep when no more events are found
        ConfigRetriever::WORKER_SLEEP_TIME           => (int) env('BUNDLES_ADDONS_PROJECTOR_SLEEP_TIME', 300),
        // Make sure to not have long running processes. After every execution cycle the worker will sleep
        // For the time defined above
        // As a best practice, please make sure the worker will not stay up for more than 1200s (20 minutes)
        ConfigRetriever::WORKER_MAX_EXECUTION_CYCLES => 12 // max number of execution cycles until worker is stopped
    ],
    BusinessGroupSitesProjector::WORKER_NAME           => [
        // Time for a worker / projector to sleep when no more events are found
        ConfigRetriever::WORKER_SLEEP_TIME           => 300,
        // Make sure to not have long running processes. After every execution cycle the worker will sleep
        // For the time defined above
        // As a best practice, please make sure the worker will not stay up for more than 1200s (20 minutes)
        ConfigRetriever::WORKER_MAX_EXECUTION_CYCLES => 12 // max number of execution cycles until worker is stopped
    ],
    TimerPendingPurchasesCommandHandler::WORKER_NAME   => [
        // Time for a worker / projector to sleep when no more events are found
        ConfigRetriever::WORKER_SLEEP_TIME           => 300,
        // Make sure to not have long running processes. After every execution cycle the worker will sleep
        // For the time defined above
        // As a best practice, please make sure the worker will not stay up for more than 1200s (20 minutes)
        ConfigRetriever::WORKER_MAX_EXECUTION_CYCLES => 12 // max number of execution cycles until worker is stopped
    ],
    SendEmailsCommandHandler::WORKER_NAME => [
        // Time for a worker / projector to sleep when no more events are found
        ConfigRetriever::WORKER_SLEEP_TIME           => 2, // seconds
        // Make sure to not have long running processes. After every execution cycle the worker will sleep
        // For the time defined above
        // As a best practice, please make sure the worker will not stay up for more than 1200s (20 minutes)
        ConfigRetriever::WORKER_MAX_EXECUTION_CYCLES => 50 // max number of execution cycles until worker is stopped
    ]
];
