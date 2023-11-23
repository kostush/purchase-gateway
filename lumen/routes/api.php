<?php
/** @var \Laravel\Lumen\Routing\Router $router */
$router->group(
    [
        'prefix'     => 'api/v1/',
        'middleware' => 'Session'
    ],
    function () use ($router) {
        $router->group(
            ['middleware' => ['GenerateSessionId', 'NGLogger']],
            function () use ($router) {
                //Health check
                $router->get('healthCheck', ['uses' => 'PurchaseGatewayHealthCheckController@retrieve']);

                // Purchase init
                $router->post(
                    'purchase/init',
                    [
                        'middleware' => ['key_auth', 'force_cascade'],
                        'uses'       => 'InitPurchaseController@post'
                    ]
                );

                // Purchase process
                $router->post(
                    'purchase/process',
                    [
                        'middleware' => ['cors', 'token_auth', 'trim_strings'],
                        'uses'       => 'ProcessPurchaseController@post'
                    ]
                );

                // Retrieve integration events
                $router->get(
                    'integration-events/retrieve/{siteId}/eventDate/{eventDate}',
                    ['middleware' => 'key_auth', 'uses' => 'RetrieveIntegrationEventsController@retrieve']
                );

                // Retrieve biller transaction by item id
                $router->get(
                    'billerTransaction/search/session/{sessionId}',
                    ['uses' => 'RetrieveBillerTransactionController@retrieve']
                );

                //Retrieve failed billers for a given session id
                $router->get(
                    'failedBillers/session/{sessionId}',
                    ['uses' => 'RetrieveFailedBillersController@retrieve']
                );

                // for QA purpose
                $router->post(
                    'billerTransaction/search/session/{sessionId}',
                    ['uses' => 'RetrieveBillerTransactionController@retrieve']
                );

                // Captcha validation
                $router->post(
                    '/purchase/validate-captcha/{step}',
                    [
                        'middleware' => [
                            'key_auth',
                            'token_auth'
                        ],
                        'uses'       => 'CaptchaValidationController@validateCaptcha'
                    ]
                );


                // Update transaction based on postback payload
                $router->post(
                    '/purchase/thirdParty/postback/{sessionId}',
                    ['uses' => 'ThirdPartyPostbackController@postback']
                );

                // Purchase 3D Lookup
                $router->post(
                    'threed/lookup',
                    [
                        'middleware' => ['cors', 'token_auth', 'trim_strings'],
                        'uses'       => 'LookupThreeDController@post'
                    ]
                );

                // authenticate
                $router->get(
                    '/purchase/threedtwo/authenticate',
                    [
                        'uses' => 'AuthenticateThreeDTwoController@authenticatePurchase',
                        'as'   => 'threedtwo.authenticate'
                    ]
                );

                // authenticate
                $router->get(
                    '/purchase/threedtwo/generateAuthUrl',
                    [
                        'uses' => 'AuthenticateThreeDTwoController@generateAuthUrl',
                        'as'   => 'threedtwo.generateAuthUrl'
                    ]
                );
            }
        );
    }
);

/**
 * This group serves as the translation layer between the Client -> PG -> Mgpg -> Pg -> Client
 *
 * See:
 * https://wiki.mgcorp.co/pages/viewpage.action?pageId=152047990
 */
$router->group(
    [
        'prefix'     => 'mgpg/api/v1/',
        'middleware' => ['Session', 'Correlation'],
        'namespace'  => 'Mgpg'
    ],
    function () use ($router) {
        $router->group(
            ['middleware' => ['GenerateCorrelationId', 'GenerateSessionId', 'NGLogger']],
            function () use ($router) {
                // Purchase init
                $router->post(
                    'purchase/init',
                    [
                        'middleware' => ['key_auth', 'force_cascade'],
                        'uses'       => 'InitPurchaseController@post',
                    ]
                );

                $router->post(
                    'purchase/process',
                    [
                        'middleware' => ['cors', 'token_auth', 'trim_strings'],
                        'uses'       => 'ProcessPurchaseController@post',
                    ]
                );

                $router->post(
                    'rebill-update/init',
                    [
                        'middleware' => ['key_auth', 'force_cascade'],
                        'uses'       => 'InitRebillUpdateController@post',
                    ]
                );

                $router->post(
                    'rebill-update/process',
                    [
                        'middleware' => ['cors', 'token_auth', 'trim_strings'],
                        'uses'       => 'ProcessRebillUpdateController@post',
                    ]
                );

                $router->get(
                    '/purchase/threed/authenticate/{jwt}',
                    [
                        'middleware' => ['GenerateSessionId', 'sessionIdToken'],
                        'uses'       => 'AuthenticateThreeDController@authenticatePurchase',
                        'as'         => 'mgpg.threed.authenticate'
                    ]
                );

                // Purchase 3D Lookup
                $router->post(
                    'threed/lookup',
                    [
                        'middleware' => ['cors', 'token_auth', 'trim_strings'],
                        'uses'       => 'LookupThreeDController@post'
                    ]
                );

                $router->post(
                    '/return/{jwt}',
                    [
                        'uses' => 'ThirdPartyReturnController@performReturn',
                        'as'   => 'mgpg.threed.return'
                    ]
                );

                $router->post(
                    '/postback/{jwt}',
                    [
                        'uses' => 'ThirdPartyPostbackController@performPostback',
                        'as'   => 'mgpg.threed.postback'
                    ]
                );

                $router->post(
                    '/purchase/validate-captcha/{step}',
                    [
                        'middleware' => [
                            'key_auth',
                            'token_auth'
                        ],
                        'uses'       => 'CaptchaValidationController@validateCaptcha'
                    ]
                );

                $router->post('/refund', ['uses' => 'RefundController@post']);
                $router->post('/cancel-rebill', ['uses' => 'CancelRebillController@post']);
                $router->post('/disable-access', ['uses' => 'DisableAccessController@post']);
            }
        );
    }
);

/**
 * in this group NGLogger is not needed because its functionality was handled by SessionIdToken.
 * In some cases, we don't have a proper sessionId, SessionId is available after decode, and in this case
 * we still need to log the errors that comes from invalid token or session.
 * Also the request is logged when decoding the sessionId, in order to have consistency with the rest of the
 * process payment steps
 */
$router->group(
    [
        'prefix'     => 'api/v1/',
        'middleware' => ['GenerateSessionId', 'sessionIdToken']
    ],
    function () use ($router) {
        // authenticate
        $router->get(
            '/purchase/threed/authenticate/{jwt}',
            [
                'uses' => 'AuthenticateThreeDController@authenticatePurchase',
                'as'   => 'threed.authenticate'
            ]
        );

        // complete
        $router->post(
            '/purchase/threed/complete/{jwt}',
            [
                'uses' => 'CompleteThreeDController@completePurchase',
                'as'   => 'threed.complete'
            ]
        );

        // thirdParty redirect
        $router->get(
            '/purchase/thirdParty/redirect/{jwt}',
            [
                'uses' => 'ThirdPartyRedirectController@redirectPurchase',
                'as'   => 'thirdParty.redirect'
            ]
        );

        // return from third party biller
        $router->get(
            'purchase/thirdParty/return/{jwt}',
            [
                'uses' => 'ThirdPartyReturnController@performReturn',
                'as'   => 'thirdParty.return'
            ]
        );
    }
);
/**
 * This endpoint is part of MGPG integration mentioned above line 108.
 * https://wiki.mgcorp.co/pages/viewpage.action?pageId=152047990
 */
$router->group(
    [
        'prefix'     => 'mgpg/api/v1/',
        'middleware' => ['Session', 'Correlation'],
        'namespace'  => 'Mgpg'
    ],
    function () use ($router) {
        $router->group(
            ['middleware' => ['GenerateCorrelationId', 'GenerateSessionId', 'sessionIdToken']],
            function () use ($router) {
                $router->post(
                    '/purchase/threed/complete/{jwt}',
                    [
                        'uses' => 'CompleteThreeDController@completePurchase',
                        'as'   => 'mgpg.threed.complete'
                    ]
                );
            }
        );
    }
);

$router->group(
    [
        'prefix'     => 'api/v2/',
        'middleware' => 'Session'
    ],
    function () use ($router) {
        $router->group(
            ['middleware' => ['sessionIdToken']],
            function () use ($router) {
                $router->get(
                    '/purchase/threed/complete/{jwt}',
                    [
                        'uses' => 'SimplifiedCompleteThreeDController@completePurchase',
                        'as'   => 'threed.simplified.complete'
                    ]
                );
            }
        );
    }
);
