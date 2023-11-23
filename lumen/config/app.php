<?php

return [
    'version' => '1.64.4',
    'name'    => 'PurchaseGateway',
    'feature' => [
        'common_fraud_enable_for'      => [
            'init'    => [
                'join'    => true,
                'sec_rev' => true,
            ],
            'process' => [
                'new_credit_card'      => true,
                'existing_credit_card' => true,
            ],
        ],
        'cascade_service_enabled'       => true,
        'event_ingestion_communication' => [
            'send_fraud_velocity_event' => true,
            'send_general_bi_events'    => false,
            'send_3ds_fraud_event'      => true,
        ],
        'legacy_api_import'             => true
    ],
];
