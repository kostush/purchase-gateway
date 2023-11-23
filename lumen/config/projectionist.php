<?php

use ProBillerNG\Projection\Domain\Projectionist\Position;
use ProBillerNG\Projection\Domain\ConfigRetriever;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use ProBillerNG\PurchaseGateway\Domain\Projector\BusinessGroupSitesProjector;

return [
    ConfigRetriever::DEFAULT_CONFIG_NAME => [
        // What type of tracking to use
        // uses event creation date
        ConfigRetriever::PROJECTIONIST_POSITION_TRACKING_TYPE => Position::TRACKING_TYPE_DATE,
    ],
    BundleAddonsProjector::WORKER_NAME => [
        // What type of tracking to use
        // uses event id, which should be an integer
        ConfigRetriever::PROJECTIONIST_POSITION_TRACKING_TYPE => Position::TRACKING_TYPE_INT,
    ],
    BusinessGroupSitesProjector::WORKER_NAME => [
        // What type of tracking to use
        // uses event id, which should be an integer
        ConfigRetriever::PROJECTIONIST_POSITION_TRACKING_TYPE => Position::TRACKING_TYPE_INT,
    ]
];
