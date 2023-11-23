<?php

return [
    'maxNumberOfAttempts'       => 5, // Number of tentatives.
    'delayBeforeRetryInSeconds' => 60, // Number of seconds between every attempt.
    'connectionTimeout'         => 10, // Number of seconds to wait while trying to connect to a server
    'timeout'                   => 10, // Total timeout of the request in seconds
];
