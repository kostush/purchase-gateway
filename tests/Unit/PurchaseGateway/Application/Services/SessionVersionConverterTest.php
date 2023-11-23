<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services;

use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionConversionException;
use ProBillerNG\PurchaseGateway\Application\Services\SessionVersionConverter;
use Tests\UnitTestCase;

class SessionVersionConverterTest extends UnitTestCase
{
    // current base version - do not update this
    private $sessionV10 = [
        'version'                   => 10,
        'atlasFields'               => [
            'atlasCode' => 'atlasCodeString',
            'atlasData' => 'atlasDataString',
        ],
        'billerMapping'             => [],
        'binRouting'                => [],
        'cascade'                   => [
            'currencyCode' => 'USD',
            'biller'       => 'rocketgate',
        ],
        'fraudAdvice'               => [
            'ip'                      => '41.250.70.42',
            'email'                   => 'test@email.com',
            'zip'                     => 'testZip',
            'bin'                     => '123456',
            'initCaptchaAdvised'      => false,
            'initCaptchaValidated'    => false,
            'processCaptchaAdvised'   => false,
            'processCaptchaValidated' => false,
            'blacklistedOnInit'       => false,
            'blacklistedOnProcess'    => false,
            'captchaAlreadyValidated' => false,
            'timesBlacklisted'        => 0,
        ],
        'initializedItemCollection' => [
            [
                'itemId'                => '54895f62-3273-45cd-a3b8-5226a3517bde',
                'addonId'               => '0b45e8b1-78a3-465e-ad4e-becbc1fb1331',
                'bundleId'              => 'c757e101-cb74-4161-b524-33ba2f288d41',
                'siteId'                => '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2',
                'subscriptionId'        => null,
                'initialDays'           => 2,
                'rebillDays'            => 30,
                'initialAmount'         => 1,
                'rebillAmount'          => 39.99,
                'tax'                   => null,
                'transactionCollection' => [
                    [
                        'state'         => 'approved',
                        'transactionId' => 'eae70ce8-b376-4126-9bbe-7f7fcf6a7745',
                    ]
                ],
                'isTrial'               => true,
                'isCrossSale'           => false,
            ],
            [
                'itemId'                => '6a0e52cb-ed1a-4e54-ab30-6938fa762b9b',
                'addonId'               => '94dd40cf-3de3-4c5b-a214-1cf6bd580683',
                'bundleId'              => 'a0a3aa08-f106-410d-ae6e-34f92d98f09b',
                'siteId'                => '29a1ee81-cf3d-11e9-8c91-0cc47a283dd2',
                'subscriptionId'        => null,
                'initialDays'           => 2,
                'rebillDays'            => 30,
                'initialAmount'         => 1,
                'rebillAmount'          => 34.97,
                'tax'                   => null,
                'transactionCollection' => [
                    [
                        'state'         => 'approved',
                        'transactionId' => '74c2e3aa-09a6-44ef-8286-21a65303c01a',
                    ]
                ],
                'isTrial'               => true,
                'isCrossSale'           => true,
            ],
        ],
        'paymentType'               => 'cc',
        'publicKeyIndex'            => 0,
        'sessionId'                 => '000028c2-ea6e-4f62-bf28-4a6044c032b0',
        'state'                     => 'valid',
        'userInfo'                  => [
            'address'        => null,
            'city'           => null,
            'country'        => 'MA',
            'email'          => '',
            'firstName'      => '',
            'ipAddress'      => '41.250.70.42',
            'lastName'       => '',
            'password'       => '',
            'phoneNumber'    => '',
            'state'          => null,
            'username'       => '',
            'zipCode'        => '',
            'billerMemberId' => '',
        ],
        'gatewaySubmitNumber'       => 0,
        'isExpired'                 => false,
        'memberId'                  => null,
        'entrySiteId'               => null,
        'paymentTemplateCollection' => [
            [
                'templateId'                   => '563ea645-a609-4698-8a73-1058b0ada8ee',
                'firstSix'                     => '472644',
                'expirationYear'               => '2022',
                'expirationMonth'              => '3',
                'lastUsedDate'                 => '2020-03-03 14:59:26',
                'createdAt'                    => '2020-03-03 14:59:26',
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4',
            ]
        ],
        'existingMember'            => false,
    ];

    // session version 22
    private $sessionV22 = [
        'version'                       => 22,
        'atlasFields'                   => [
            'atlasCode' => 'atlasCodeString',
            'atlasData' => 'atlasDataString',
        ],
        'billerMapping'                 => [],
        'binRouting'                    => [],
        'cascade'                       => [
            'billers'               => [
                'rocketgate'
            ],
            'currentBillerSubmit'   => 0,
            'currentBiller'         => 'rocketgate',
            'currentBillerPosition' => 0,
        ],
        'fraudAdvice'                   => [
            'ip'                      => '41.250.70.42',
            'email'                   => 'test@email.com',
            'zip'                     => 'testZip',
            'bin'                     => '123456',
            'initCaptchaAdvised'      => false,
            'initCaptchaValidated'    => false,
            'processCaptchaAdvised'   => false,
            'processCaptchaValidated' => false,
            'blacklistedOnInit'       => false,
            'blacklistedOnProcess'    => false,
            'captchaAlreadyValidated' => false,
            'timesBlacklisted'        => 0,
            'forceThreeD'             => false,
            'detectThreeDUsage'       => false,
            'forceThreeDOnInit'       => false,
            'forceThreeDOnProcess'    => false
        ],
        'initializedItemCollection'     => [
            [
                'itemId'                => '54895f62-3273-45cd-a3b8-5226a3517bde',
                'addonId'               => '0b45e8b1-78a3-465e-ad4e-becbc1fb1331',
                'bundleId'              => 'c757e101-cb74-4161-b524-33ba2f288d41',
                'siteId'                => '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2',
                'subscriptionId'        => null,
                'initialDays'           => 2,
                'rebillDays'            => 30,
                'initialAmount'         => 1,
                'rebillAmount'          => 39.99,
                'tax'                   => null,
                'transactionCollection' => [
                    [
                        'state'         => 'approved',
                        'transactionId' => 'eae70ce8-b376-4126-9bbe-7f7fcf6a7745',
                        'newCCUsed'     => false,
                        'billerName'    => 'rocketgate',
                        'acs'           => null,
                        'pareq'         => null,
                        'redirectUrl'   => null,
                        'crossSales'    => null,
                        'isNsf'         => null
                    ]
                ],
                'isTrial'               => true,
                'isCrossSale'           => false,
                'isCrossSaleSelected'   => false,
            ],
            [
                'itemId'                => '6a0e52cb-ed1a-4e54-ab30-6938fa762b9b',
                'addonId'               => '94dd40cf-3de3-4c5b-a214-1cf6bd580683',
                'bundleId'              => 'a0a3aa08-f106-410d-ae6e-34f92d98f09b',
                'siteId'                => '29a1ee81-cf3d-11e9-8c91-0cc47a283dd2',
                'subscriptionId'        => null,
                'initialDays'           => 2,
                'rebillDays'            => 30,
                'initialAmount'         => 1,
                'rebillAmount'          => 34.97,
                'tax'                   => null,
                'transactionCollection' => [
                    [
                        'state'         => 'approved',
                        'transactionId' => '74c2e3aa-09a6-44ef-8286-21a65303c01a',
                        'newCCUsed'     => false,
                        'billerName'    => 'rocketgate',
                        'acs'           => null,
                        'pareq'         => null,
                        'redirectUrl'   => null,
                        'crossSales'    => null,
                        'isNsf'         => null
                    ]
                ],
                'isTrial'               => true,
                'isCrossSale'           => true,
                'isCrossSaleSelected'   => false
            ],
        ],
        'paymentType'                   => 'cc',
        'publicKeyIndex'                => 0,
        'sessionId'                     => '000028c2-ea6e-4f62-bf28-4a6044c032b0',
        'state'                         => 'valid',
        'userInfo'                      => [
            'address'        => null,
            'city'           => null,
            'country'        => 'MA',
            'email'          => '',
            'firstName'      => '',
            'ipAddress'      => '41.250.70.42',
            'lastName'       => '',
            'password'       => '',
            'phoneNumber'    => '',
            'state'          => null,
            'username'       => '',
            'zipCode'        => '',
            'billerMemberId' => '',
        ],
        'gatewaySubmitNumber'           => 0,
        'isExpired'                     => false,
        'memberId'                      => null,
        'entrySiteId'                   => null,
        'paymentTemplateCollection'     => [
            [
                'templateId'                   => '563ea645-a609-4698-8a73-1058b0ada8ee',
                'firstSix'                     => '472644',
                'expirationYear'               => '2022',
                'expirationMonth'              => '3',
                'lastUsedDate'                 => '2020-03-03 14:59:26',
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4',
                'billerName'                   => 'rocketgate',
            ]
        ],
        'existingMember'                => false,
        'redirectUrl'                   => null,
        'currency'                      => 'USD',
        'paymentMethod'                 => null,
        'trafficSource'                 => 'ALL',
        'postbackUrl'                   => null,
        'purchaseId'                    => null,
        'fraudRecommendationCollection' => [
            [
                'severity' => 'Allow',
                'code'     => 1000,
                'message'  => 'Allow_Transaction',
            ]
        ],
    ];

    public static $sessionLatestVersion = [
        'version'                       => SessionVersionConverter::LATEST_VERSION,
        'atlasFields'                   => [
            'atlasCode' => 'atlasCodeString',
            'atlasData' => 'atlasDataString',
        ],
        'billerMapping'                 => [],
        'binRouting'                    => [],
        'cascade'                       => [
            'billers'               => [
                'rocketgate'
            ],
            'currentBillerSubmit'   => 0,
            'currentBiller'         => 'rocketgate',
            'currentBillerPosition' => 0,
            'removedBillersFor3DS'  => null
        ],
        'fraudAdvice'                   => [
            'ip'                      => '41.250.70.42',
            'email'                   => 'test@email.com',
            'zip'                     => 'testZip',
            'bin'                     => '123456',
            'initCaptchaAdvised'      => false,
            'initCaptchaValidated'    => false,
            'processCaptchaAdvised'   => false,
            'processCaptchaValidated' => false,
            'blacklistedOnInit'       => false,
            'blacklistedOnProcess'    => false,
            'captchaAlreadyValidated' => false,
            'timesBlacklisted'        => 0,
            'forceThreeD'             => false,
            'detectThreeDUsage'       => false,
            'forceThreeDOnInit'       => false,
            'forceThreeDOnProcess'    => false
        ],
        'initializedItemCollection'     => [
            [
                'itemId'                => '54895f62-3273-45cd-a3b8-5226a3517bde',
                'addonId'               => '0b45e8b1-78a3-465e-ad4e-becbc1fb1331',
                'bundleId'              => 'c757e101-cb74-4161-b524-33ba2f288d41',
                'siteId'                => '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2',
                'subscriptionId'        => null,
                'initialDays'           => 2,
                'rebillDays'            => 30,
                'initialAmount'         => 1,
                'rebillAmount'          => 39.99,
                'tax'                   => null,
                'transactionCollection' => [
                    [
                        'state'               => 'approved',
                        'transactionId'       => 'eae70ce8-b376-4126-9bbe-7f7fcf6a7745',
                        'newCCUsed'           => false,
                        'billerName'          => 'rocketgate',
                        'acs'                 => null,
                        'pareq'               => null,
                        'redirectUrl'         => null,
                        'crossSales'          => null,
                        'isNsf'               => null,
                        'deviceCollectionUrl' => null,
                        'deviceCollectionJwt' => null,
                        'deviceFingerprintId' => null,
                        'threeDStepUpUrl'     => null,
                        'threeDStepUpJwt'     => null,
                        'md'                  => null,
                        'threeDFrictionless'  => false,
                        'threeDVersion'       => null
                    ]
                ],
                'isTrial'               => true,
                'isCrossSale'           => false,
                'isCrossSaleSelected'   => false,
            ],
            [
                'itemId'                => '6a0e52cb-ed1a-4e54-ab30-6938fa762b9b',
                'addonId'               => '94dd40cf-3de3-4c5b-a214-1cf6bd580683',
                'bundleId'              => 'a0a3aa08-f106-410d-ae6e-34f92d98f09b',
                'siteId'                => '29a1ee81-cf3d-11e9-8c91-0cc47a283dd2',
                'subscriptionId'        => null,
                'initialDays'           => 2,
                'rebillDays'            => 30,
                'initialAmount'         => 1,
                'rebillAmount'          => 34.97,
                'tax'                   => null,
                'transactionCollection' => [
                    [
                        'state'               => 'approved',
                        'transactionId'       => '74c2e3aa-09a6-44ef-8286-21a65303c01a',
                        'newCCUsed'           => false,
                        'billerName'          => 'rocketgate',
                        'acs'                 => null,
                        'pareq'               => null,
                        'redirectUrl'         => null,
                        'crossSales'          => null,
                        'isNsf'               => null,
                        'deviceCollectionUrl' => null,
                        'deviceCollectionJwt' => null,
                        'deviceFingerprintId' => null,
                        'threeDStepUpUrl'     => null,
                        'threeDStepUpJwt'     => null,
                        'md'                  => null,
                        'threeDFrictionless'  => false,
                        'threeDVersion'       => null
                    ]
                ],
                'isTrial'               => true,
                'isCrossSale'           => true,
                'isCrossSaleSelected'   => false
            ],
        ],
        'paymentType'                   => 'cc',
        'publicKeyIndex'                => 0,
        'sessionId'                     => '000028c2-ea6e-4f62-bf28-4a6044c032b0',
        'state'                         => 'valid',
        'userInfo'                      => [
            'address'        => null,
            'city'           => null,
            'country'        => 'MA',
            'email'          => '',
            'firstName'      => '',
            'ipAddress'      => '41.250.70.42',
            'lastName'       => '',
            'password'       => '',
            'phoneNumber'    => '',
            'state'          => null,
            'username'       => '',
            'zipCode'        => '',
            'billerMemberId' => '',
        ],
        'gatewaySubmitNumber'           => 0,
        'isExpired'                     => false,
        'memberId'                      => null,
        'entrySiteId'                   => null,
        'paymentTemplateCollection'     => [
            [
                'templateId'                   => '563ea645-a609-4698-8a73-1058b0ada8ee',
                'firstSix'                     => '472644',
                'expirationYear'               => '2022',
                'expirationMonth'              => '3',
                'lastUsedDate'                 => '2020-03-03 14:59:26',
                'createdAt'                    => '2020-03-03 14:59:26',
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4',
                'billerName'                   => 'rocketgate',
            ]
        ],
        'existingMember'                => false,
        'redirectUrl'                   => null,
        'currency'                      => 'USD',
        'paymentMethod'                 => null,
        'trafficSource'                 => 'ALL',
        'postbackUrl'                   => null,
        'purchaseId'                    => null,
        'fraudRecommendationCollection' => [
            [
                'severity' => 'Allow',
                'code'     => 1000,
                'message'  => 'Allow_Transaction',
            ]
        ],
        'paymentTemplateId' => null,
        'skipVoid' => false,
        'creditCardWasBlacklisted' => false
    ];

    /**
     * @test
     * @return array
     * @throws SessionConversionException
     */
    public function it_should_return_an_array_after_conversion_to_latest_version(): array
    {
        $sessionVersionConverter = new SessionVersionConverter();
        $session                 = $this->sessionV10;
        $convertedPayload        = $sessionVersionConverter->convert($session);

        $this->assertIsArray($convertedPayload);

        return $convertedPayload;
    }

    /**
     * @test
     * @param array $convertedPayload Converted Session Payload
     * @depends it_should_return_an_array_after_conversion_to_latest_version
     * @return void
     */
    public function it_should_return_an_array_containing_a_version_key_after_conversion_to_latest_version(
        $convertedPayload
    ): void {
        $this->assertArrayHasKey('version', $convertedPayload);
    }

    /**
     * @test
     * @param array $convertedPayload Converted Session Payload
     * @depends it_should_return_an_array_after_conversion_to_latest_version
     * @return void
     */
    public function it_should_return_an_array_containing_the_updated_version_value_after_conversion_to_latest_version(
        $convertedPayload
    ): void {
        $this->assertSame(SessionVersionConverter::LATEST_VERSION, $convertedPayload['version']);
    }

    /**
     * @test
     * @param array $convertedPayload Converted Session Payload
     * @depends it_should_return_an_array_after_conversion_to_latest_version
     * @return void
     */
    public function it_should_return_an_array_containing_the_correct_structure_after_conversion_to_latest_version(
        $convertedPayload
    ) {
        $this->assertSame(self::$sessionLatestVersion, $convertedPayload);
    }

    /**
     * @test
     * @return void
     * @throws SessionConversionException
     */
    public function it_should_return_an_array_containing_the_correct_structure_after_conversion_from_v_22_to_latest_version(): void
    {
        $sessionVersionConverter = new SessionVersionConverter();
        $convertedSession        = $sessionVersionConverter->convert($this->sessionV22);

        self::$sessionLatestVersion['paymentTemplateCollection'][0]['createdAt'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->assertEquals(self::$sessionLatestVersion, $convertedSession);
    }
}
