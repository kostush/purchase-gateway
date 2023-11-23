<?php
declare(strict_types=1);

namespace Tests\System\Mgpg\ProcessPurchase;

use GuzzleHttp\Client;
use ProbillerMGPG\ClientApi;
use Tests\SystemTestCase;

abstract class ProcessPurchaseBase extends SystemTestCase
{
    public const    BUNDLE_ID                   = '4475820e-2956-11e9-b210-d663bd873d93';

    public const    ADDON_ID                    = '4e1b0d7e-2956-11e9-b210-d663bd873d93';

    public const    PORNHUB_PREMIUM_SITE_ID     = '299d3e6b-cf3d-11e9-8c91-0cc47a283dd2';

    protected const COUNTRY_ROCKETGATE          = 'US';

    public const    REALITY_KINGS_SITE_ID       = '8e34c94e-135f-4acb-9141-58b3a6e56c74';

    protected const COUNTRY_ANY_BILLER          = 'US';

    protected const TESTING_SITE           = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';
    protected const TESTING_SITE_NO_FRAUD  = '0ee56671-1eaf-414c-9b0e-ee7f1a8ded96';

    public const INCORRECT_SAGA_STEP_PROCESS = 9602;

    protected const FORCE_3DS_SITE_ID           = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';

    public const    INVALID_CC_NUMBER           = '1234567890123456';

    public const PAYSITES_BUSINESS_GROUP_ID     = '07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1';

    public const TESTING_BUSINESS_GROUP_ID     = 'ef67349f-9199-46d9-89a9-f097755f12cd';

    public const INITIAL_AMOUNT                 = 29.99;

    public const MGPG_CLASSIC_THREEDS_V1_CURRENCY = 'INR';

    /**
     * @var ClientApi
     */
    protected $clientApi;

    public function setUp(): void
    {
        parent::setUp();

        $clientId = config('clientapis.mgpg.aadAuth.clientId');
        $secret   = config('clientapis.mgpg.aadAuth.clientSecret');
        $env      = config('clientapis.mgpg.aadAuth.env');

        $this->clientApi = new ClientApi(new Client(['base_uri' => $env]), $clientId, $secret);
    }

    /**
     * @param array $data Existing Member Params
     *
     * @return array
     * @throws \Exception
     */
    protected function processExistingMemberPurchasePayload(array $data = []): array
    {
        $payload = $this->processPurchasePayloadWithOneSelectedCrossSale();

        // we will add custom member info for each individual scenario
        unset($payload['member']);

        return array_merge($payload, $data);
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithOneSelectedCrossSale(
        string $siteId = self::TESTING_SITE
    ): array {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'             => $siteId,
            'member'             => [
                'email'       => $this->faker->email,
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'selectedCrossSells' => [
                [
                    'bundleId' => self::BUNDLE_ID,
                    'addonId'  => self::ADDON_ID,
                    'siteId'   => '4c22fba2-f883-11e8-8eb2-f2801f1b9fd1',
                ]
            ],
            'payment'            => [
                'type'                => 'cc',
                'method'              => 'visa',
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithTwoSelectedCrossSale(): array
    {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'             => self::REALITY_KINGS_SITE_ID,
            'member'             => [
                'email'       => $username . '@EPS.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'selectedCrossSells' => [
                [
                    'bundleId' => self::BUNDLE_ID,
                    'addonId'  => self::ADDON_ID,
                    'siteId'   => '4c22fba2-f883-11e8-8eb2-f2801f1b9fd1',
                ],
                [
                    'bundleId' => self::BUNDLE_ID,
                    'addonId'  => self::ADDON_ID,
                    'siteId'   => self::REALITY_KINGS_SITE_ID,
                ]
            ],
            'payment'            => [
                'type'                => 'cc',
                'method'              => 'visa',
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithNoSelectedCrossSale(
        string $siteId = self::REALITY_KINGS_SITE_ID
    ): array {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $this->faker->email,
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'method'              => 'alipay',
                'type'                => 'cc',
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithSpaces(string $siteId = self::REALITY_KINGS_SITE_ID): array
    {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $this->faker->email,
                'username'    => "   $username   ",
                'password'    => 'test12345',
                'firstName'   => '    Francois     ',
                'lastName'    => '     Second    ',
                'countryCode' => 'CA',
                'zipCode'     => '   h1h1h1',
                'address1'    => '   123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal   ',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'type'                => 'cc',
                'method'              => 'visa',
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     */
    protected function processPurchasePayloadNameWithNumbers(
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ): array {
        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => 'testPurchasegateway@test.mindgeek.com',
                'username'    => 'testPurchasegateway',
                'password'    => 'test12345',
                'firstName'   => 'Mister François  1234  ',
                'lastName'    => 'Axe Second  34242  ',
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'ccNumber'            => $this->faker->creditCardNumber('Visa'),
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     */
    protected function processPurchasePayloadWithMinimumMemberPayload(
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ): array {
        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $this->faker->firstName . '@test.mindgeek.com',
                'username'    => 'testPurchasegateway',
                'password'    => 'test12345',
                'firstName'   => 'John',
                'lastName'    => 'Smith',
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1'
            ],
            'payment' => [
                'ccNumber'            => $this->faker->creditCardNumber('MasterCard'),
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param bool   $forceRocketgate Force rocketgate
     * @param string $siteId          Site id
     * @param array  $data
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSale(
        bool $forceRocketgate = false,
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID,
        array $data = []
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->businessGroupTestingXApiKey();
        $headers = $this->initPurchaseHeaders($xApiKey);

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload($siteId, $data),
            $headers
        );
    }

    /**
     * @param string|null $xApiKey X Api Key
     *
     * @return array
     */
    protected function initPurchaseHeaders(?string $xApiKey = null): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key'    => $xApiKey ?? $this->paysitesXApiKey()
        ];
    }

    /**
     * @return string
     */
    protected function validInitPurchaseRequestUri(): string
    {
        return '/mgpg/api/v1/purchase/init';
    }

    /**
     * @param string $siteId SiteId
     * @param array  $data
     *
     * @return array
     * @throws \Exception
     */
    public function initPurchasePayload(
        string $siteId = self::REALITY_KINGS_SITE_ID,
        array $data = []
    ): array {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'bundleId' => self::BUNDLE_ID,
                'addonId'  => self::ADDON_ID,
            ]
        );

        $countryCode = ($siteId === self::PORNHUB_PREMIUM_SITE_ID) ? self::COUNTRY_ROCKETGATE : self::COUNTRY_ANY_BILLER;

        return [
            'otherData'         => $data['otherData'] ?? [],
            'overrides'         => $data['overrides'] ?? [],
            'siteId'            => $siteId,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => $data['currency'] ?? 'USD',
            'clientIp'          => '10.10.109.185',
            'paymentType'       => $data['paymentType'] ?? 'cc',
            'paymentMethod'     => $data['paymentMethod'] ?? 'visa',
            'clientCountryCode' => $countryCode,
            'amount'            => $data['amount'] ?? 29.99,
            'initialDays'       => 5,
            'rebillDays'        => 30,
            'rebillAmount'      => 29.99,
            'atlasCode'         => 'NDU1MDk1OjQ4OjE0Nw',
            'atlasData'         => 'atlas data example',
            'isTrial'           => false,
            'postbackUrl'       => $this->faker->url,
            'redirectUrl'       => $this->faker->url,
            'tax'               => [
                'initialAmount'    => [
                    'beforeTaxes' => $data['tax']['initialAmount']['beforeTaxes'] ?? 28.56,
                    'taxes'       => 1.43,
                    'afterTaxes'  => $data['tax']['initialAmount']['afterTaxes'] ?? 29.99
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => 28.56,
                    'taxes'       => 1.43,
                    'afterTaxes'  => 29.99
                ],
                'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                'taxName'          => 'VAT',
                'taxRate'          => 0.05,
                'taxType'          => 'VAT',
            ],
            'crossSellOptions'  => [
                [
                    'bundleId'     => (string) $bundle->bundleId(),
                    'addonId'      => (string) $bundle->addonId(),
                    'siteId'       => $siteId,
                    'initialDays'  => 3,
                    'rebillDays'   => 30,
                    'amount'       => 1.00,
                    'rebillAmount' => 34.97,
                    'isTrial'      => false,
                    'tax'          => [
                        'initialAmount'    => [
                            'beforeTaxes' => 0.95,
                            'taxes'       => 0.05,
                            'afterTaxes'  => 1.00
                        ],
                        'rebillAmount'     => [
                            'beforeTaxes' => 33.30,
                            'taxes'       => 1.67,
                            'afterTaxes'  => 34.97
                        ],
                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                        'taxName'          => 'HST',
                        'taxRate'          => 0.05,
                        'taxType'          => 'sales',
                    ],
                    'otherData' => $data['otherData'] ?? [],
                ]
            ]
        ];
    }


    /**
     * @param string $siteId SiteId
     * @param array  $data
     *
     * @return array
     * @throws \Exception
     */
    public function initPurchaseGiftcardsPayload(
        string $siteId = self::REALITY_KINGS_SITE_ID,
        array $data = []
    ): array {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'bundleId' => self::BUNDLE_ID,
                'addonId'  => self::ADDON_ID,
            ]
        );

        $countryCode = ($siteId === self::PORNHUB_PREMIUM_SITE_ID) ? self::COUNTRY_ROCKETGATE : self::COUNTRY_ANY_BILLER;

        return [
            'otherData'         => $data['otherData'] ?? [],
            'overrides'         => $data['overrides'] ?? [],
            'siteId'            => $siteId,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => $data['currency'] ?? 'USD',
            'clientIp'          => '10.10.109.185',
            'paymentType'       => $data['paymentType'] ?? 'cc',
            'paymentMethod'     => $data['paymentMethod'] ?? 'visa',
            'clientCountryCode' => $countryCode,
            'amount'            => $data['amount'] ?? 29.99,
            'initialDays'       => 0,
            'atlasCode'         => 'NDU1MDk1OjQ4OjE0Nw',
            'atlasData'         => 'atlas data example',
            'isTrial'           => false,
            'postbackUrl'       => $this->faker->url,
            'redirectUrl'       => $this->faker->url,
            'tax'               => [
                'initialAmount'    => [
                    'beforeTaxes' => $data['tax']['initialAmount']['beforeTaxes'] ?? 28.56,
                    'taxes'       => 1.43,
                    'afterTaxes'  => $data['tax']['initialAmount']['afterTaxes'] ?? 29.99
                ],
                'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                'taxName'          => 'VAT',
                'taxRate'          => 0.05,
                'taxType'          => 'VAT',
            ],
            'crossSellOptions'  => [
                [
                    'bundleId'     => (string) $bundle->bundleId(),
                    'addonId'      => (string) $bundle->addonId(),
                    'siteId'       => $siteId,
                    'initialDays'  => 0,
                    'amount'       => 2.10,
                    'isTrial'      => false,
                    'tax'          => [
                        'initialAmount'    => [
                            'beforeTaxes' => 2.00,
                            'taxes'       => 0.05,
                            'afterTaxes'  => 2.10
                        ],
                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                        'taxName'          => 'HST',
                        'taxRate'          => 0.05,
                        'taxType'          => 'sales',
                    ],
                    'otherData' => $data['otherData'] ?? [],
                ],
                [
                    'bundleId'     => (string) $bundle->bundleId(),
                    'addonId'      => (string) $bundle->addonId(),
                    'siteId'       => $siteId,
                    'initialDays'  => 0,
                    'amount'       => 2.10,
                    'isTrial'      => false,
                    'tax'          => [
                        'initialAmount'    => [
                            'beforeTaxes' => 2.00,
                            'taxes'       => 0.05,
                            'afterTaxes'  => 2.10
                        ],
                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                        'taxName'          => 'HST',
                        'taxRate'          => 0.05,
                        'taxType'          => 'sales',
                    ],
                    'otherData' => $data['otherData'] ?? [],
                ]
            ]
        ];

    }

    /**
     * @param bool   $forceRocketgate Force rocketgate
     * @param string $siteId          Site id
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleAndExcessiveInitialDays(
        bool $forceRocketgate = false,
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->businessGroupTestingXApiKey();
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }

        $payload = $this->initPurchasePayload($siteId);

        // overwrite initialDays
        $payload['crossSellOptions'][0]['initialDays'] = 10001;

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );

        return $response;
    }

    protected function forceRocketgate()
    {
        $this->override(
            [
                "cachedConfigService" => [
                    "getAllBillerConfigs" => [
                        [
                            "name"           => "rocketgate",
                            "type"           => 0,
                            "supports3DS"    => true,
                            "isLegacyBiller" => false,
                            "sendAllCharges" => false,
                            "createdAt"      => null,
                            "updatedAt"      => null
                        ]
                    ]
                ]
            ]
        );
    }

    public function override(array $overrides = [])
    {
        $this->clientApi->setOverrides($overrides);

        // Sets MGPG ClientApi that is normally injected on post/get calls through Lumen for this one
        $this->app->instance(ClientApi::class, $this->clientApi);
    }

    /**
     * @param array $data            Existing Member Params
     * @param bool  $forceRocketgate Force rocketgate
     *
     * @return ProcessPurchaseBase
     * @throws \Exception
     */
    protected function initExistingMemberWithoutSubscriptionId(
        array $data = [],
        bool $forceRocketgate = false
    ): ProcessPurchaseBase {
        $headers = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }
        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initExistingMemberWithoutSubscriptionIdPayload($data),
            $headers
        );

        return $response;
    }

    /**
     * @param array $data Existing Member Params
     *
     * @return array
     * @throws \Exception
     */
    protected function initExistingMemberWithoutSubscriptionIdPayload(array $data = []): array
    {
        $payload                = $this->initPurchasePayload(self::TESTING_SITE);
        $payload['memberId']    = $data['memberId'] ?? $this->faker->uuid;
        $payload['entrySiteId'] = $data['entrySiteId'] ?? $this->faker->uuid;

        return $payload;
    }

    /**
     * @param array $data            Existing Member Params
     * @param bool  $forceRocketgate Force rocketgate
     *
     * @return ProcessPurchaseBase
     * @throws \Exception
     */
    protected function initExistingMemberWithSubscriptionId(
        array $data = [],
        bool $forceRocketgate = false
    ): ProcessPurchaseBase {
        $headers = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initExistingMemberWithSubscriptionIdPayload($data),
            $headers
        );

        return $response;
    }

    /**
     * @param array $data Existing Member Params
     *
     * @return array
     * @throws \Exception
     */
    protected function initExistingMemberWithSubscriptionIdPayload(array $data = []): array
    {
        $payload                   = $this->initPurchasePayload(self::TESTING_SITE);
        $payload['memberId']       = $data['memberId'] ?? $this->faker->uuid;
        $payload['subscriptionId'] = $data['subscriptionId'] ?? $this->faker->uuid;
        foreach ($payload['crossSellOptions'] as $index => $crossSell) {
            $payload['crossSellOptions'][$index]['subscriptionId'] = $payload['subscriptionId'];

            if (!empty($data['crossSellOptions']['initialDays'])) {
                $payload['crossSellOptions'][$index]['initialDays'] = $payload['crossSellOptions'][$index]['initialDays'] ?? $data['crossSellOptions']['initialDays'];
            }
            if (!empty($data['crossSellOptions']['rebillDays'])) {
                $payload['crossSellOptions'][$index]['rebillDays'] = $payload['crossSellOptions'][$index]['rebillDays'] ?? $data['crossSellOptions']['rebillDays'];
            }
        }

        return $payload;
    }

    /**
     * @param bool $forceRocketgate Force rocketgate
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithTwoCrossSales(bool $forceRocketgate = false)
    {
        $initPayload = $this->initPurchasePayload();

        $initPayload['crossSellOptions'][1] = [
            'bundleId'     => self::BUNDLE_ID,
            'addonId'      => self::ADDON_ID,
            'siteId'       => self::PORNHUB_PREMIUM_SITE_ID,
            'initialDays'  => 3,
            'rebillDays'   => 30,
            'amount'       => 1.00,
            'rebillAmount' => 34.97,
            'isTrial'      => false,
            'tax'          => [
                'initialAmount'    => [
                    'beforeTaxes' => 0.95,
                    'taxes'       => 0.05,
                    'afterTaxes'  => 1.00
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => 33.30,
                    'taxes'       => 1.67,
                    'afterTaxes'  => 34.97
                ],
                'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                'taxName'          => 'VAT',
                'taxRate'          => 0.05,
            ]
        ];

        $headers = $this->initPurchaseHeaders();

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPayload,
            $headers
        );
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithoutTax()
    {
        $initPurchasePayloadWithoutTax = $this->initPurchasePayload();
        unset($initPurchasePayloadWithoutTax['tax']);

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPurchasePayloadWithoutTax,
            $this->initPurchaseHeaders()
        );

        return $response;
    }

    /**
     * @param bool  $forceRocketgate Force rocketgate
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleWithoutTax(bool $forceRocketgate = false, array $data = [])
    {
        $initPurchasePayloadWithoutTax = $this->initPurchasePayload(self::TESTING_SITE, $data);

        $headers = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }

        unset($initPurchasePayloadWithoutTax['tax']);
        unset($initPurchasePayloadWithoutTax['crossSellOptions'][0]['tax']);

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPurchasePayloadWithoutTax,
            $headers
        );
    }

    /**
     * @param bool $forceRocketgate Force rocketgate
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleWithoutRebill(bool $forceRocketgate = false)
    {
        $initPurchasePayloadWithoutRebill = $this->initPurchasePayload();

        $headers = $this->initPurchaseHeaders();

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }

        unset($initPurchasePayloadWithoutRebill['rebillAmount']);
        unset($initPurchasePayloadWithoutRebill['rebillDays']);
        unset($initPurchasePayloadWithoutRebill['tax']['rebillAmount']);
        unset($initPurchasePayloadWithoutRebill['crossSellOptions'][0]['rebillDays']);
        unset($initPurchasePayloadWithoutRebill['crossSellOptions'][0]['rebillDays']);
        unset($initPurchasePayloadWithoutRebill['crossSellOptions'][0]['tax']['rebillAmount']);

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPurchasePayloadWithoutRebill,
            $headers
        );
    }

    /**
     * @param bool   $forceRocketgate Force rocketgate
     * @param string $siteId          Site id
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithoutCrossSales(
        bool $forceRocketgate = false,
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ) {
        $initPurchasePayloadWithoutTax = $this->initPurchasePayload($siteId);

        unset($initPurchasePayloadWithoutTax['crossSellOptions']);

        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $_ENV['PAYSITES_API_KEY'];
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceRocketgate) {
            $this->forceRocketgate();
        }

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $initPurchasePayloadWithoutTax,
            $headers
        );
    }

    /**
     * @param bool $forceRocketgate Force rocketgate
     *
     * @return string
     * @throws \Exception
     */
    protected function performTwoDeclinedPurchases($forceRocketgate = false): string
    {
        //force a failed transaction with rocketgate
        $response = $this->initDeclinedPurchaseProcessWithOneCrossSale($forceRocketgate);
        $response->seeHeader('X-Auth-Token');

        $content = json_decode($this->response->getContent(), true);

        //first attempt
        $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithInvalidCreditCard(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        //second attempt
        $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithInvalidCreditCard(),
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        return $content['sessionId'];
    }

    /**
     * @param bool $forceRocketgate Force rocketgate
     * @param bool $forceNetbilling Force netbilling
     *
     * @return ProcessPurchaseBase
     * @throws \Exception
     */
    protected function initDeclinedPurchaseProcessWithOneCrossSale(
        bool $forceRocketgate = false,
        bool $forceNetbilling = false
    ) {
        $payload = $this->initPurchasePayload();

        $headers = $this->initPurchaseHeaders();

        if ($forceRocketgate) {
            // insert values that will force a rocketgate decline
            $payload['amount']                             = 0.01;
            $payload['rebillAmount']                       = 0.01;
            $payload['tax']['initialAmount']['afterTaxes'] = 0.01;
            $payload['tax']['rebillAmount']['afterTaxes']  = 0.01;

            $this->forceRocketgate();
        }

        if ($forceNetbilling) {
            // insert values that will force a netbilling decline
            $payload['amount']                             = 91;
            $payload['rebillAmount']                       = 91;
            $payload['tax']['initialAmount']['afterTaxes'] = 91;
            $payload['tax']['rebillAmount']['afterTaxes']  = 91;

            $headers['X-Force-Cascade'] = 'test-netbilling';
        }

        return $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );
    }

    /**
     * @return string
     */
    protected function validProcessPurchaseRequestUri(): string
    {
        return '/mgpg/api/v1/purchase/process';
    }

    /**
     * @param bool $forceRocketgate Force rocketgate
     *
     * @return mixed
     * @throws \Exception
     */

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithInvalidCreditCard(
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ): array {
        $payload = $this->processPurchasePayloadWithOneSelectedCrossSale($siteId);

        $payload['payment']['ccNumber'] = '1234123412341234';

        return $payload;
    }

    /**
     * @param string $token token.
     *
     * @return array
     */
    protected function processPurchaseHeaders(string $token): array
    {
        $token = 'Bearer ' . $token;

        return [
            'Content-Type'  => 'application/json',
            'Authorization' => $token
        ];
    }

    /**
     * @param string $sessionId     Session id
     * @param string $transactionId Transaction id
     *
     * @return array
     */
    protected function postbackPayloadForEpoch(string $sessionId, string $transactionId): array
    {
        $payload = [
            'amount'           => '10',
            'ans'              => 'Y245724UU |2354302288',
            'country'          => 'RO',
            'currency'         => 'USD',
            'email'            => 'user@test.mindgeek.com',
            'event_datetime'   => '2020-05-28T10:50:05.970Z',
            'ipaddress'        => '188.26.230.109',
            'last4'            => '1111',
            'localamount'      => '10',
            'member_id'        => '2354302288',
            'name'             => 'Customer',
            'ngSessionId'      => $sessionId,
            'ngTransactionId'  => $transactionId,
            'order_id'         => '2354302288',
            'password'         => 'E7UNApJNXEUK2Dd2',
            'payment_subtype'  => 'VS',
            'payment_type'     => 'CC',
            'pi_code'          => 'InvoiceProduct68252',
            'postalcode'       => '123456',
            'prepaid'          => 'N',
            'session_id'       => '949bb227-6b54-49ce-aee9-eb5086577188',
            'submit_count'     => '1',
            'trans_amount'     => '10',
            'trans_amount_usd' => '10',
            'trans_currency'   => 'USD',
            'transaction_id'   => '1206684681',
            'username'         => 'dqjViXFqYm8M4EIi',
            'zip'              => '123456'
        ];

        ksort($payload);
        $str = '';

        foreach ($payload as $k => $v) {
            if (!empty($v)) {
                $str .= $k . trim($v);
            }
        }

        $key = hash_hmac('md5', $str, '9fcc3657fa2e670746373a92f40d7448');

        $payload['epoch_digest'] = $key;

        return $payload;
    }

    /**
     * @param string $transactionId Transaction id.
     *
     * @return array
     */
    protected function postbackPayloadForQysso(string $transactionId): array
    {
        $payload = [
            "reply_code"      => "000",
            "reply_desc"      => "SUCCESS",
            "trans_id"        => "1",
            "trans_date"      => "12\/21\/2020 1:43:07 PM",
            "trans_amount"    => "55.3",
            "trans_currency"  => "0",
            "trans_order"     => $transactionId,
            "Order"           => $transactionId,
            "merchant_id"     => "7162012",
            "client_fullname" => "Test Test",
            "client_phone"    => "15143593555",
            "client_email"    => "testqysso201@probiller.mindgeek.com",
            "payment_details" => "Visa .... 0000"
        ];

        $signatureData = [
            $payload['trans_id'],
            $payload['trans_order'],
            $payload['reply_code'],
            $payload['trans_amount'],
            $payload['trans_currency'],
            'KI0ZQO62KP'
            // personalHashKey
        ];

        $signature = base64_encode(hash('sha256', implode("", $signatureData), true));

        $payload['signature'] = $signature;

        return $payload;
    }

    /**
     * @param bool   $forceNetbilling Force Netblling
     * @param string $siteId          Site id
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleForcedBillerAsNetbilling(
        bool $forceNetbilling = false,
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $_ENV['PAYSITES_API_KEY'];
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceNetbilling) {
            $headers['X-Force-Cascade'] = 'test-netbilling';
        }

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload($siteId),
            $headers
        );

        return $response;
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithNoSelectedCrossSaleForNetbillingBiller(
        string $siteId = self::REALITY_KINGS_SITE_ID
    ): array {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@EPS.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => 'Mister',
                'lastName'    => 'Axe',
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'type'                => 'cc',
                'method'              => 'visa',
                'ccNumber'            => $ccNumber,
                'cvv'                 => '887',
                'cardExpirationMonth' => '05',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @return string[]
     */
    public function buildDataToTrigger3dsV1(): array
    {
        return [
            'currency' => self::MGPG_CLASSIC_THREEDS_V1_CURRENCY,
            'siteId'   => self::TESTING_SITE,
            'xApiKey'  => $this->businessGroupTestingXApiKey()
        ];
    }

    /**
     * @param bool   $forceNetbilling Force Netblling
     * @param string $siteId          Site id
     *
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleWithNetbilling(
        bool $forceNetbilling = false,
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $_ENV['PAYSITES_API_KEY'];
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceNetbilling) {
            $headers['X-Force-Cascade'] = 'test-netbilling';
            $this->forceNetbilling();
        }

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload($siteId),
            $headers
        );

        return $response;
    }

    protected function forceNetbilling()
    {
        $this->override(
            [
                'fraudService' => [
                    'callInitVisitor' => [
                        0 => [
                            'severity' => 'Allow',
                            'code' => 1000,
                            'message' => 'Allow',
                        ],
                    ],
                ],
                'cascade' => [
                    'callCascades' => [
                        'billers' => [
                            0 => 'netbilling',
                        ],
                    ],
                ],
                'cachedConfigService' => [
                    'getAllBillerConfigs' => [
                        0 => [
                            'name' => 'netbilling',
                            'type' => 0,
                            'supports3DS' => false,
                            'isLegacyBiller' => false,
                            'sendAllCharges' => false,
                            'createdAt' => NULL,
                            'updatedAt' => NULL,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithNoSelectedForNetbillingBiller(
        string $siteId = self::REALITY_KINGS_SITE_ID
    ): array {
        $username = 'testPurchase' . random_int(100, 999);

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@EPS.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => 'Mister',
                'lastName'    => 'Axe',
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                'type'                => 'cc',
                'method'              => 'visa',
                'ccNumber'            => $_ENV['NETBILLING_CARD_NUMBER'],
                'cvv'                 => $_ENV['NETBILLING_CARD_CVV2'],
                'cardExpirationMonth' => '05',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }
}
