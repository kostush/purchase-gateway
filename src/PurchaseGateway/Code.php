<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway;

class Code
{
    //100-199 Purchase Gateway generic exceptions
    public const PURCHASE_GATEWAY_EXCEPTION            = 100;
    public const SESSION_EXPIRED                       = 101;
    public const TOKEN_EXPIRED                         = 102;
    public const SESSION_CONVERSION_FAILED             = 103;
    public const DOMAIN_EVENT_CONVERSION_FAILED        = 104;
    public const ILLEGAL_STATE_TRANSITION_EXCEPTION    = 105;
    public const REPOSITORY_EXCEPTION                  = 106;
    public const STATE_RESTORE_EXCEPTION               = 107;
    public const AGGREGATE_ID_NOT_SET_ON_EVENT         = 108;
    public const PURCHASE_ENTITY_CANNOT_BE_CREATED     = 109;
    public const ITEM_IS_MISSING_FROM_COLLECTION       = 110;
    public const REPOSITORY_CONNECTION_EXCEPTION       = 111;
    public const FORCE_CASCADE_EXCEPTION               = 112;
    public const SITE_NOT_EXIST_EXCEPTION              = 113;
    public const INITIALIZED_ITEM_COLLECTION_NOT_FOUND = 114;
    public const INVALID_TOKEN                         = 115;
    public const NO_BODY_OR_HEADER_RECEIVED_EXCEPTION  = 116;
    public const FAILED_DEPENDENCY                     = 117;
    public const CROSS_SALE_SITE_NOT_EXIST_EXCEPTION   = 118;

    //300-399 Purchase Gateway exceptions
    public const PURCHASE_GATEWAY_NOT_FOUND = 300;

    //400-499 criteria exceptions
    public const CRITERIA_NOT_FOUND = 400;

    //500-599
    public const INVALID_QUERY   = 500;
    public const INVALID_COMMAND = 501;

    // 600-699
    public const UNABLE_TO_CREATE_PURCHASE_PROCESS = 600;

    // 700-799 Invalid UUIDs
    const INVALID_UUID_SUPPLIED = 700;

    //1000-1999
    public const INIT_INFO_NOT_FOUND_ON_SESSION  = 1000;
    public const BUNDLE_NOT_FOUND                = 1001;
    public const ITEM_NOT_FOUND                  = 1002;
    public const ITEM_COULD_NOT_BE_RESTORED      = 1003;
    public const SESSION_NOT_FOUND               = 1010;
    public const TRANSACTION_NOT_FOUND_EXCEPTION = 1011;

    //2000-2999
    public const INVALID_DATETIME_EXCEPTION    = 2001;
    public const NON_CROSS_SALE_ITEM_EXCEPTION = 2002;

    //3000-3999 PurchaseGatewayQueryHttpDTO exceptions
    //const PURCHASE_GATEWAY_QUERY_HTTP_DTO_INVALID = 3000;
    public const TRANSACTION_ALREADY_PROCESSED_EXCEPTION = 3032;

    //4000-4999 FilePersistence exceptions
    public const FILE_PERSISTENCE_NOT_ACCESSIBLE = 4000;

    //5000-5100 Validation errors
    public const INVALID_FIELD_SUPPLIED_IN_PAYLOAD = 5000;
    public const INVALID_TRANSACTION_STATE         = 5001;
    public const INVALID_STATE                     = 5002;
    public const INVALID_THREE_D_VERSION           = 5003;

    // 5100-5199 Integration event creation exceptions
    public const CREATE_INTEGRATION_EVENT_EXCEPTION          = 5100;
    public const RETRIEVE_TRANSACTION_DATA_EXCEPTION         = 5101;
    public const INVALID_TRANSACTION_DATA_RESPONSE_EXCEPTION = 5102;
    public const TRANSACTION_DATA_NOT_FOUND_EXCEPTION        = 5103;
    public const UNKNOWN_BILLER_ID_EXCEPTION                 = 5104;
    public const UNKNOWN_BILLER_NAME_EXCEPTION               = 5105;
    public const NETBILLING_CONTROL_KEYWORD_NOT_FOUND        = 5106;
    public const ABORT_TRANSACTION_EXCEPTION                 = 5107;

    // 5200 - 5299 Captcha validation exceptions
    public const INVALID_STEP_FOR_CAPTCHA_VALIDATION_EXCEPTION      = 5200;
    public const CANNOT_VALIDATE_CAPTCHA_PROCESS_EXCEPTION          = 5201;
    public const CAPTCHA_NOT_VALIDATED_EXCEPTION                    = 5203;
    public const CANNOT_PROCESS_PURCHASE_WITHOUT_CAPTCHA_VALIDATION = 5204;

    // 5555 - 5600 Application errors
    public const APPLICATION_EXCEPTION_INVALID_SESSION_ID = 5555;
    public const MISSING_REDIRECT_URL                     = 5556;
    public const MISSING_PARES_AND_MD                     = 5557;
    public const MISSING_MANDATORY_COMPLETE_PARAMS        = 5558;
    public const BAD_GATEWAY                              = 5559;

    public const SESSION_ALREADY_PROCESSES       = 5566;
    public const INVALID_PAYLOAD_EXCEPTION       = 5567;
    public const NO_BILLERS_IN_CASCADE_EXCEPTION = 5568;

    // 7000 - 7100 Price Transformation Service exception
    public const INVALID_AMOUNT          = 7000;
    public const INVALID_PERCENTAGE      = 7001;
    public const INVALID_BIN             = 7002;
    public const INVALID_CURRENCY_SYMBOL = 7003;
    public const INVALID_CURRENCY        = 7004;
    public const INVALID_DAYS            = 7005;

    //8000 - 8100 Transaction User Information exceptions
    public const INVALID_USER_INFO_USERNAME     = 8001;
    public const INVALID_USER_INFO_FIRST_NAME   = 8002;
    public const INVALID_USER_INFO_LAST_NAME    = 8003;
    public const INVALID_USER_INFO_PASSWORD     = 8004;
    public const INVALID_USER_INFO_COUNTRY      = 8005;
    public const INVALID_USER_INFO_ZIP_CODE     = 8006;
    public const INVALID_USER_INFO_PHONE_NUMBER = 8007;
    public const INVALID_USER_INFO_EMAIL        = 8008;

    //8101 - 8110 Validations exceptions
    public const INVALID_ZIP_CODE                            = 8101;
    public const INVALID_IP                                  = 8102;
    public const CREDIT_CARD_DATE_EXPIRED                    = 8103;
    public const CREDIT_CARD_INVALID_EXPIRY_DATE             = 8116;
    public const CREDIT_CARD_IS_BLACKLISTED                  = 8888;
    public const UNSUPPORTED_PAYMENT_TYPE                    = 8104;
    public const INVALID_LAST_FOUR_EXCEPTION                 = 8105;
    public const INVALID_PAYMENT_INFORMATION                 = 8106;
    public const INVALID_ENTRY_SITE_SUBSCRIPTION_COMBINATION = 8107;
    public const INVALID_BUNDLE_ADDON_COMBINATION            = 8108;
    public const INVALID_ITEM_UUID_SUPPLIED                  = 8109;
    public const UNSUPPORTED_PAYMENT_METHOD                  = 8110;
    public const DUPLICATED_PURCHASE_PROCESS_REQUEST         = 8111;

    //8111 - 8120 EmailService exceptions
    public const EMAIL_SERVICE_EXCEPTION                    = 8112;
    public const EMAIL_SERVICE_UNDEFINED_TRANSLATION_OBJECT = 8113;
    public const EMAIL_SERVICE_COULD_NOT_SEND_EMAIL         = 8114;
    public const EMAIL_SERVICE_CLIENT_COULD_NOT_SEND_EMAIL  = 8115;

    //8121 - 8130 BillerMapping service exceptions
    public const BILLER_MAPPING_API_EXCEPTION   = 8121;
    public const BILLER_MAPPING_ERROR_EXCEPTION = 8122;
    public const BILLER_MAPPING_TYPE_EXCEPTION  = 8123;
    public const UNKNOWN_BILLER_DTO_EXCEPTION   = 8124;
    public const BILLER_MAPPING_EXCEPTION       = 8125;
    public const INVALID_BILLER_FIELDS_DATA     = 8126;

    //8131 - 8140 BinRouting service exceptions
    public const BIN_ROUTING_CODE_TYPE_EXCEPTION      = 8131;
    public const BIN_ROUTING_CODE_ERROR_EXCEPTION     = 8132;
    public const BIN_ROUTING_API_ERROR_EXCEPTION      = 8133;
    public const BIN_ROUTING_UNKNOWN_BILLER_EXCEPTION = 8134;

    //8141 - 8150 Transaction service exceptions
    public const TRANSACTION_SERVICE_BILLER_NOT_SUPPORTED         = 8141;
    public const TRANSACTION_SERVICE_INVALID_RESPONSE_EXCEPTION   = 8142;
    public const TRANSACTION_SERVICE_API_EXCEPTION                = 8143;
    public const UNABLE_TO_PROCESS_TRANSACTION_EXCEPTION          = 8144;
    public const UNABLE_TO_COMPLETE_THREE_D_TRANSACTION_EXCEPTION = 8145;

    //8151 - 8160 Fraud service exceptions
    public const FRAUD_ADVICE_SERVICE_API_SUPPORTED         = 8151;
    public const FRAUD_ADVICE_SERVICE_TRANSLATION_EXCEPTION = 8152;
    public const FRAUD_SERVICE_CS_CODE_TYPE_EXCEPTION       = 8153;
    public const FRAUD_SERVICE_CS_CODE_API_EXCEPTION        = 8154;
    public const FRAUD_SERVICE_CS_CLIENT_EXCEPTION          = 8155;

    //8161 - 8170 Payment template exceptions
    public const PAYMENT_TEMPLATE_CODE_TYPE_EXCEPTION      = 8161;
    public const PAYMENT_TEMPLATE_CODE_ERROR_EXCEPTION     = 8162;
    public const PAYMENT_TEMPLATE_API_ERROR_EXCEPTION      = 8163;
    public const RETRIEVE_PAYMENT_TEMPLATE_EXCEPTION       = 8164;
    public const PAYMENT_TEMPLATE_DATA_NOT_FOUND_EXCEPTION = 8165;
    public const INVALID_PAYMENT_TEMPLATE_ID               = 8166;
    public const INVALID_PAYMENT_TEMPLATE_LAST_FOUR        = 8167;

    //8171 - 8180 Member profile gateway exceptions
    public const MEMBER_PROFILE_GATEWAY_TYPE_EXCEPTION      = 8171;
    public const MEMBER_PROFILE_GATEWAY_ERROR_EXCEPTION     = 8172;
    public const MEMBER_PROFILE_GATEWAY_API_EXCEPTION       = 8173;
    public const MEMBER_PROFILE_GATEWAY_NOT_FOUND_EXCEPTION = 8147;

    //8181 - 8190 Bundle Management Admin Exceptions
    public const BUNDLE_MANAGEMENT_ADMIN_CODE_ERROR_EXCEPTION = 8181;
    public const BUNDLE_MANAGEMENT_ADMIN_API_EXCEPTION        = 8182;

    //8191 - 8210 Site Admin Exceptions
    public const SITE_ADMIN_CODE_ERROR_EXCEPTION = 8191;
    public const SITE_ADMIN_API_EXCEPTION        = 8192;

    //9000 - 9100 Consumer Exceptions
    public const COMMAND_FACTORY_EXCEPTION                 = 9000;
    public const COMMAND_FACTORY_UNKNOWN_COMMAND_EXCEPTION = 9001;
    public const AUTHENTICATE_RESULT_EXCEPTION             = 9002;

    //9101 - 9200 Cascade service exceptions
    public const CASCADE_TRANSLATOR_EXCEPTION    = 9101;
    public const INVALID_NEXT_BILLER             = 9102;
    public const INVALID_FORCE_CASCADE_EXCEPTION = 9103;

    //9201 - 9300 Fraud exception
    public const BLOCKED_DUE_TO_FRAUD_EXCEPTION = 9201;

    //9301 - 9400 Failed billers exceptions
    public const FAILED_BILLERS_NOT_FOUND = 9301;
    public const PURCHASE_WAS_SUCCESSFUL  = 9302;

    //9401 - 9500 NuData service exceptions
    public const NU_DATA_NOT_FOUND_EXCEPTION      = 9401;
    public const RETRIEVE_NU_DATA_SCORE_EXCEPTION = 9402;

    //9501 - 9600 Config service exceptions
    public const CONFIG_SERVICE_RESPONSE_EXCEPTION    = 9501;
    public const CONFIG_SERVICE_CREATE_SITE_EXCEPTION = 9502;

    // Mapping error codes from dependencies services.
    const QYSSO_MALFORMED_PAYLOAD_EXCEPTION = 200;

    //9601 - 9700 MGPG API Exceptions
    public const MGPG_VALIDATION_EXCEPTION                          = 9601;
    public const MGPG_ERROR_RESPONSE                                = 9602;
    public const MGPG_REBILL_FIELD_REQUIRED                         = 9603;
    public const BUSINESS_TRANSACTION_OPERATION_NOT_FOUND_EXCEPTION = 9604;
    public const INVALID_BUSINESS_TRANSACTION_OPERATION_EXCEPTION   = 9605;
    public const MGPG_RETURN_VALIDATION_EXCEPTION                   = 9606;

    // Error CLASSIFICATION Type NSF
    public const ERROR_CLASSIFICATION_NSF                           = "NSF";

    protected static $messages = [
        self::PURCHASE_GATEWAY_EXCEPTION            => 'Purchase Gateway exception!',
        self::SESSION_EXPIRED                       => 'Session expired',
        self::TOKEN_EXPIRED                         => 'Token expired',
        self::INVALID_TOKEN                         => 'Invalid token',
        self::BAD_GATEWAY                           => 'Bad Gateway',
        self::SESSION_CONVERSION_FAILED             => 'Session could not be converted',
        self::DOMAIN_EVENT_CONVERSION_FAILED        => 'Domain event could not be converted',
        self::ILLEGAL_STATE_TRANSITION_EXCEPTION    => 'Illegal transition state change',
        self::STATE_RESTORE_EXCEPTION               => 'Cannot restore state',
        self::AGGREGATE_ID_NOT_SET_ON_EVENT         => 'Aggregate Id not set on event: %s',
        self::PURCHASE_ENTITY_CANNOT_BE_CREATED     => 'Purchase entity could not be created',
        self::ITEM_IS_MISSING_FROM_COLLECTION       => 'Item is missing from the collection',
        self::REPOSITORY_EXCEPTION                  => 'Repository exception with message: %s and %s error code',
        self::PURCHASE_GATEWAY_NOT_FOUND            => 'Purchase Gateway not found!',
        self::INVALID_UUID_SUPPLIED                 => 'The supplied UUID is not valid',
        self::INVALID_QUERY                         => 'Invalid query!',
        self::INVALID_COMMAND                       => 'Invalid command! Expecting: %s, Received: %s',
        self::INIT_INFO_NOT_FOUND_ON_SESSION        => 'Init info not found on session',
        self::BUNDLE_NOT_FOUND                      => 'Bundle not found',
        self::ITEM_NOT_FOUND                        => 'Item not found',
        self::ITEM_COULD_NOT_BE_RESTORED            => 'Item could not be restored',
        self::UNABLE_TO_CREATE_PURCHASE_PROCESS     => 'Unable to create PurchaseProcess object',
        self::INVALID_DATETIME_EXCEPTION            => 'Invalid datetime provided',
        self::NON_CROSS_SALE_ITEM_EXCEPTION         => 'Can not mark a non cross sale item as selected cross sale',
        self::FILE_PERSISTENCE_NOT_ACCESSIBLE       => 'Persistence file is not accessible or not exists',
        self::INVALID_FIELD_SUPPLIED_IN_PAYLOAD     => 'A field supplied int the request payload is invalid',
        self::INVALID_TRANSACTION_STATE             => 'Invalid transaction state: %s',
        self::INVALID_STATE                         => 'Invalid state',
        self::INVALID_THREE_D_VERSION               => 'Invalid threeD version: %s',
        self::REPOSITORY_CONNECTION_EXCEPTION       => 'Cannot connect to repository',
        self::FORCE_CASCADE_EXCEPTION               => 'Invalid force cascade value was provided',
        self::SITE_NOT_EXIST_EXCEPTION              => 'The site does not exist',
        self::CROSS_SALE_SITE_NOT_EXIST_EXCEPTION   => 'The cross sale site does not exist: %s',
        self::INITIALIZED_ITEM_COLLECTION_NOT_FOUND => 'An initialized item collection is not associated with the supplied session id',
        self::SESSION_NOT_FOUND                     => 'The session does not exist',
        self::NO_BODY_OR_HEADER_RECEIVED_EXCEPTION  => 'No body/header received',
        self::FAILED_DEPENDENCY                     => 'Failed dependency on %s.',

        self::TRANSACTION_NOT_FOUND_EXCEPTION               => 'Transaction not found',
        self::TRANSACTION_ALREADY_PROCESSED_EXCEPTION       => 'Transaction was already processed',

        // 5100-5199 Integration event creation exceptions
        self::CREATE_INTEGRATION_EVENT_EXCEPTION            => 'Cannot create integration event',
        self::RETRIEVE_TRANSACTION_DATA_EXCEPTION           => 'Error while retrieving transaction data from service',
        self::INVALID_TRANSACTION_DATA_RESPONSE_EXCEPTION   => 'Transaction data returned from service is invalid',
        self::TRANSACTION_DATA_NOT_FOUND_EXCEPTION          => 'Transaction data not found on service for id %s',
        self::UNKNOWN_BILLER_ID_EXCEPTION                   => 'Unknown biller id: %s',
        self::UNKNOWN_BILLER_NAME_EXCEPTION                 => 'Unknown biller name: %s',
        self::NETBILLING_CONTROL_KEYWORD_NOT_FOUND          => 'Control key word not found for the account id: %s',
        self::ABORT_TRANSACTION_EXCEPTION                   => 'Cannot abort transaction',

        // 5200 - 5299 Captcha validation exceptions
        self::INVALID_STEP_FOR_CAPTCHA_VALIDATION_EXCEPTION => 'Cannot validate captcha for %s',
        self::CANNOT_VALIDATE_CAPTCHA_PROCESS_EXCEPTION     => 'Cannot validate captcha for process because captcha'
                                                               . ' for init is not validated',
        self::CAPTCHA_NOT_VALIDATED_EXCEPTION               => 'Captcha not validated.',

        self::CANNOT_PROCESS_PURCHASE_WITHOUT_CAPTCHA_VALIDATION => 'Cannot process purchase because '
                                                                    . ' captcha was not validated',

        self::SESSION_ALREADY_PROCESSES                   => 'The session with id %s was already processed',
        self::INVALID_PAYLOAD_EXCEPTION                   => 'The payload is invalid. Transaction not found.',
        self::MISSING_REDIRECT_URL                        => 'The redirect url is missing',
        self::MISSING_PARES_AND_MD                        => 'The PARES and MD is missing or are invalid',
        self::MISSING_MANDATORY_COMPLETE_PARAMS           => 'The query string is invalid',
        self::NO_BILLERS_IN_CASCADE_EXCEPTION             => 'No billers in cascade.',

        // 7000 - 7100 Price Transformation Service exceptions
        self::INVALID_AMOUNT                              => 'Invalid amount',
        self::INVALID_PERCENTAGE                          => 'Invalid percentage',
        self::INVALID_BIN                                 => 'Invalid BIN given: %s',
        self::INVALID_CURRENCY_SYMBOL                     => 'Invalid currency symbol',
        self::INVALID_CURRENCY                            => 'Invalid currency: %s',
        self::INVALID_DAYS                                => 'Invalid initialDays or rebillDays. It needs to be a value between 0 and 10,000.',

        //8000 - 8100 Transaction User Information exceptions
        self::INVALID_USER_INFO_USERNAME                  => 'Invalid User Information: username!',
        self::INVALID_USER_INFO_FIRST_NAME                => 'Invalid User Information: first name!',
        self::INVALID_USER_INFO_LAST_NAME                 => 'Invalid User Information: last name!',
        self::INVALID_USER_INFO_PASSWORD                  => 'Invalid User Information: password!',
        self::INVALID_USER_INFO_PHONE_NUMBER              => 'Invalid User Information: phone number!',
        self::INVALID_USER_INFO_ZIP_CODE                  => 'Invalid User Information: zip code!',
        self::INVALID_USER_INFO_EMAIL                     => 'Invalid User Information: email!',
        self::INVALID_USER_INFO_COUNTRY                   => 'Invalid User Information: country!',

        //8101 - 8110 Validation exceptions
        self::INVALID_ZIP_CODE                            => 'Invalid zip code!',
        self::INVALID_IP                                  => 'Invalid IP!',
        self::CREDIT_CARD_DATE_EXPIRED                    => 'Credit Card date expired!',
        self::CREDIT_CARD_INVALID_EXPIRY_DATE             => 'Credit Card invalid expiry date!',
        self::CREDIT_CARD_IS_BLACKLISTED                  => 'Credit Card is blacklisted. Please use a different card.',
        self::UNSUPPORTED_PAYMENT_TYPE                    => 'The payment type %s is not supported',
        self::INVALID_LAST_FOUR_EXCEPTION                 => 'Invalid last 4 given: %s',
        self::INVALID_PAYMENT_INFORMATION                 => 'Invalid payment information field: %s',
        self::INVALID_ENTRY_SITE_SUBSCRIPTION_COMBINATION => 'Invalid entry site id subscription combination',
        self::INVALID_BUNDLE_ADDON_COMBINATION            => 'Invalid bundle - addon combination',
        self::INVALID_ITEM_UUID_SUPPLIED                  => 'The supplied item id is not an valid UUID',
        self::UNSUPPORTED_PAYMENT_METHOD                  => 'The payment method %s is not supported',
        self::DUPLICATED_PURCHASE_PROCESS_REQUEST         => 'Duplicated request. The initial one is still processing.',

        self::APPLICATION_EXCEPTION_INVALID_SESSION_ID           => 'Invalid session id!',

        //8111 - 8120 EmailService exceptions
        self::EMAIL_SERVICE_EXCEPTION                            => 'Email service exception',
        self::EMAIL_SERVICE_UNDEFINED_TRANSLATION_OBJECT         => 'Undefined translation object given',
        self::EMAIL_SERVICE_COULD_NOT_SEND_EMAIL                 => 'Could not send email',
        self::EMAIL_SERVICE_CLIENT_COULD_NOT_SEND_EMAIL          => 'Client could not send email',

        //8121 - 8130 BillerMapping exceptions
        self::BILLER_MAPPING_API_EXCEPTION                       => 'BillerMapping API - could not retrieve data',
        self::BILLER_MAPPING_ERROR_EXCEPTION                     => 'BillerMapping API - error response',
        self::BILLER_MAPPING_TYPE_EXCEPTION                      => 'BillerMapping API - Type exception',
        self::UNKNOWN_BILLER_DTO_EXCEPTION                       => 'The provided biller DTO class does not exist',
        self::BILLER_MAPPING_EXCEPTION                           => 'Unable to retrieve biller mapping. Please try again later.',
        self::INVALID_BILLER_FIELDS_DATA                         => 'Invalid biller fields',


        //8131 - 8140 BinRouting exceptions
        self::BIN_ROUTING_CODE_TYPE_EXCEPTION                    => 'Provided data is not of type: %s',
        self::BIN_ROUTING_CODE_ERROR_EXCEPTION                   => 'Error: %s, with %s error code',
        self::BIN_ROUTING_API_ERROR_EXCEPTION                    => 'API error: %s, with %s error code',
        self::BIN_ROUTING_UNKNOWN_BILLER_EXCEPTION               => 'Unknown biller name: %s',

        //8141 - 8150 Transaction service exceptions
        self::TRANSACTION_SERVICE_BILLER_NOT_SUPPORTED           => 'Biller: %s is not supported for transaction',
        self::TRANSACTION_SERVICE_INVALID_RESPONSE_EXCEPTION     => 'Invalid response from Transaction API: %s',
        self::TRANSACTION_SERVICE_API_EXCEPTION                  => 'Transaction API - could not retrieve data',
        self::UNABLE_TO_PROCESS_TRANSACTION_EXCEPTION            => 'Unable to process transaction. Please try again later.',
        self::UNABLE_TO_COMPLETE_THREE_D_TRANSACTION_EXCEPTION   => 'Cannot complete threeD transaction.',

        //8151 - 8160 Fraud service exceptions
        self::FRAUD_ADVICE_SERVICE_API_SUPPORTED                 => 'Fraud advice api call failed: %s',
        self::FRAUD_ADVICE_SERVICE_TRANSLATION_EXCEPTION         => 'Fraud advice api response translation failed: %s',
        self::FRAUD_SERVICE_CS_CODE_TYPE_EXCEPTION               => 'Provided data is not of type: %s',
        self::FRAUD_SERVICE_CS_CODE_API_EXCEPTION                => 'API error: %s, with %s error code',
        self::FRAUD_SERVICE_CS_CLIENT_EXCEPTION                  => 'Client error: %s, with %s error code',

        //8161 - 8170 Payment template exceptions
        self::PAYMENT_TEMPLATE_CODE_TYPE_EXCEPTION               => 'Provided data is not of type: %s',
        self::PAYMENT_TEMPLATE_CODE_ERROR_EXCEPTION              => 'Error: %s, with %s error code',
        self::PAYMENT_TEMPLATE_API_ERROR_EXCEPTION               => 'API error: %s, with %s error code',
        self::RETRIEVE_PAYMENT_TEMPLATE_EXCEPTION                => 'Unable to retrieve the payment template. Please provide full payment information.',
        self::PAYMENT_TEMPLATE_DATA_NOT_FOUND_EXCEPTION          => 'Payment template data not found on service for id %s',
        self::INVALID_PAYMENT_TEMPLATE_ID                        => 'Invalid Payment Template Id',
        self::INVALID_PAYMENT_TEMPLATE_LAST_FOUR                 => 'Invalid Payment Template Last Four',

        //8171 - 8180 Member profile gateway exceptions
        self::MEMBER_PROFILE_GATEWAY_TYPE_EXCEPTION              => 'Provided data is not of type: %s',
        self::MEMBER_PROFILE_GATEWAY_ERROR_EXCEPTION             => 'Error: %s, with %s error code',
        self::MEMBER_PROFILE_GATEWAY_API_EXCEPTION               => 'API error: %s, with %s error code',
        self::MEMBER_PROFILE_GATEWAY_NOT_FOUND_EXCEPTION         => 'Member Profile not found for id %s',

        //8181 - 8190 Bundle Management Admin Exceptions
        self::BUNDLE_MANAGEMENT_ADMIN_CODE_ERROR_EXCEPTION       => 'Error: %s, with %s error code',
        self::BUNDLE_MANAGEMENT_ADMIN_API_EXCEPTION              => 'API error: %s, with %s error code',

        //8191-8210
        self::SITE_ADMIN_CODE_ERROR_EXCEPTION                    => 'Error: %s, with %s error code',
        self::SITE_ADMIN_API_EXCEPTION                           => 'API error: %s, with %s error code',

        //9000 - 9100 Consumer Exceptions
        self::COMMAND_FACTORY_EXCEPTION                          => 'Cannot create command from message \'%s\' received by consumer',
        self::COMMAND_FACTORY_UNKNOWN_COMMAND_EXCEPTION          => 'Unknown message \'%s\' received by consumer',
        self::AUTHENTICATE_RESULT_EXCEPTION                      => "Cannot create authenticate result",

        //9101 - 9200 Cascade service exceptions
        self::CASCADE_TRANSLATOR_EXCEPTION                       => 'Cascade translator exception!',
        self::INVALID_NEXT_BILLER                                => 'There are no more billers available in the cascade',
        self::INVALID_FORCE_CASCADE_EXCEPTION                    => 'Invalid force cascade: %s',

        //9201 - 9300 Fraud exception
        self::BLOCKED_DUE_TO_FRAUD_EXCEPTION                     => 'Purchase blocked due to fraud.',

        //9201 - 9300 Failed billers exceptions
        self::FAILED_BILLERS_NOT_FOUND                           => 'No failed billers found for the given session id',
        self::PURCHASE_WAS_SUCCESSFUL                            => 'Bad Request, purchase was successful',

        //9401 - 9500 NuData service exceptions
        self::NU_DATA_NOT_FOUND_EXCEPTION                        => 'Nudata settings not found for business group id: %s',
        self::RETRIEVE_NU_DATA_SCORE_EXCEPTION                   => 'Unable to retrieve NuData score for session id: %s',

        //9501 - 9600 Config service exceptions
        self::CONFIG_SERVICE_RESPONSE_EXCEPTION                  => 'Invalid response from Config Service: %s',
        self::CONFIG_SERVICE_CREATE_SITE_EXCEPTION               => 'Error occurred while creating Site model',

        // Mapping error codes from dependencies services.
        self::QYSSO_MALFORMED_PAYLOAD_EXCEPTION                  => 'Input payload for decode signature is incorrect/altered',

        //9601 - 9700 MGPG API Exceptions
        self::MGPG_VALIDATION_EXCEPTION                          => 'Invalid payload data sent to MGPG: %s',
        self::MGPG_ERROR_RESPONSE                                => 'Error received from MGPG: %s',
        self::MGPG_REBILL_FIELD_REQUIRED                         => 'Rebill field is required to complete rebill update Operation.',
        self::BUSINESS_TRANSACTION_OPERATION_NOT_FOUND_EXCEPTION => 'Business transaction operation not found: %s.',
        self::INVALID_BUSINESS_TRANSACTION_OPERATION_EXCEPTION   => 'Business transaction operation not valid for subsequent operation: %s',
        self::MGPG_RETURN_VALIDATION_EXCEPTION                   => 'Data received does not match exceptations.',
    ];

    /**
     * @param int $errorCode Error code
     * @return mixed
     */
    public static function getMessage(int $errorCode)
    {
        return self::$messages[$errorCode] ?? self::$messages[self::PURCHASE_GATEWAY_NOT_FOUND];
    }
}
