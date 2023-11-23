<?php

return [
    'eventBuilder' => [
        'type' => 'jms',
        'jms'  => [
            'mappingPath' => base_path('../src/PurchaseGateway/Domain/Projector/ProjectedItem/Mapping'),
        ]
    ]
];
