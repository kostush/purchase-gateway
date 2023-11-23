<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use Tests\SystemTestCase;

abstract class ProcessPurchaseBase extends SystemTestCase
{
    public const BUNDLE_ID = '4475820e-2956-11e9-b210-d663bd873d93';
    public const ADDON_ID  = '4e1b0d7e-2956-11e9-b210-d663bd873d93';

    /**
     * PORNHUB_PREMIUM_SITE_ID belongs to the Tubes business group.
     * When using the PORNHUB_PREMIUM_SITE_ID=299b14d0-cf3d-11e9-8c91-0cc47a283dd2
     * along with COUNTRY_ROCKETGATE=RO, the cascade service will always provide
     * Rocketgate as the first biller in cascade. This is useful for the system tests
     * that were developed in the early stages of the project, when only Rocketgate
     * was supported.
     */
    protected const PORNHUB_PREMIUM_SITE_ID = '299b14d0-cf3d-11e9-8c91-0cc47a283dd2';
    protected const COUNTRY_ROCKETGATE      = 'RO';

    /**
     * REALITY_KINGS_SITE_ID belongs to the Paysites business group.
     * When using the REALITY_KINGS_SITE_ID=8e34c94e-135f-4acb-9141-58b3a6e56c74
     * along with COUNTRY_ANY_BILLER=CA, the cascade service will provide
     * Rocketgate / Netbilling / Epoch as the first biller in cascade.
     */
    protected const REALITY_KINGS_SITE_ID = '8e34c94e-135f-4acb-9141-58b3a6e56c74';

    protected const COUNTRY_ANY_BILLER = 'CA';
    protected const MEN_SITE_ID        = '299f9d47-cf3d-11e9-8c91-0cc47a283dd2';

    public const    TESTING_SITE          = 'a2d4f06f-afc8-41c9-9910-0302bd2d9531';
    protected const TESTING_SITE_NO_FRAUD = '0ee56671-1eaf-414c-9b0e-ee7f1a8ded96';

    public const RETURNING_NOT_EXPECTED_FRAUD = 'It should not receive fraud Advice. Please check fraud configuration.';
    public const THREE_D_FLOW_NOT_TRIGGERED   = 'The authenticate threeD flow was not triggered. Please check fraud configuration.';

    public const COUNTRY_CODE_NO_FRAUD = 'US';
    public const INVALID_CC_NUMBER     = '1234567890123456';

    public const FLAKY_TEST = 'This test is flaky. Run it again.';

    /**
     * @param string $siteId        SiteId.
     * @param string $currency      Currency.
     * @param null   $paymentMethod Payment method.
     * @return array
     * @throws Exception
     */
    public function initPurchasePayload(
        string $siteId = self::TESTING_SITE,
        string $currency = CurrencyCode::EUR,
        $paymentMethod = null,
        $paymentType = "cc"
    ): array {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'bundleId' => self::BUNDLE_ID,
                'addonId'  => self::ADDON_ID,
            ]
        );

        $countryCode = ($siteId === self::PORNHUB_PREMIUM_SITE_ID) ? self::COUNTRY_ROCKETGATE : self::COUNTRY_ANY_BILLER;

        return [
            'siteId'            => $siteId,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => $currency,
            'clientIp'          => '10.10.109.185',
            'paymentType'       => $paymentType,
            'paymentMethod'     => $paymentMethod,
            'clientCountryCode' => $countryCode,
            'amount'            => 29.99,
            'initialDays'       => 5,
            'rebillDays'        => 30,
            'rebillAmount'      => 29.99,
            'atlasCode'         => 'NDU1MDk1OjQ4OjE0Nw',
            'atlasData'         => 'atlas data example',
            'isTrial'           => false,
            'redirectUrl'       => $this->faker->url,
            'tax'               => [
                'initialAmount'    => [
                    'beforeTaxes' => 28.56,
                    'taxes'       => 1.43,
                    'afterTaxes'  => 29.99
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
                    'siteId'       => self::TESTING_SITE_NO_FRAUD,
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
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $data Existing Member Params
     * @return array
     * @throws Exception
     */
    protected function initExistingMemberWithoutSubscriptionIdPayload(array $data = []): array
    {
        $payload                = $this->initPurchasePayload();
        $payload['memberId']    = $data['memberId'] ?? $this->faker->uuid;
        $payload['entrySiteId'] = $data['entrySiteId'] ?? $this->faker->uuid;
        return $payload;
    }

    /**
     * @param array $data Existing Member Params
     * @return array
     * @throws Exception
     */
    protected function initExistingMemberWithSubscriptionIdPayload(array $data = []): array
    {
        $payload                   = $this->initPurchasePayload();
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
     * @param string $siteId Site id
     * @return array
     * @throws Exception
     */
    protected function processPurchasePayloadWithNoSelectedCrossSale(
        string $siteId = self::TESTING_SITE
    ): array {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@test.mindgeek.com',
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
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param array $data Existing Member Params
     * @return array
     * @throws Exception
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
     * @return array
     * @throws Exception
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
                'countryCode' => self::COUNTRY_CODE_NO_FRAUD,
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
                    'siteId'   => self::TESTING_SITE_NO_FRAUD,
                ]
            ],
            'payment'            => [
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => '2027',
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     * @return array
     * @throws Exception
     */
    protected function processPurchasePayloadWithInvalidCreditCard(
        string $siteId = ProcessPurchaseBase::TESTING_SITE
    ): array {
        $payload = $this->processPurchasePayloadWithOneSelectedCrossSale($siteId);

        $payload['payment']['ccNumber'] = '1234123412341234';

        return $payload;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function processPurchasePayloadWithTwoSelectedCrossSale(): array
    {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');
        return [
            'siteId'             => self::TESTING_SITE,
            'member'             => [
                'email'       => $username . '@test.mindgeek.com',
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
                    'siteId'   => self::TESTING_SITE,
                ]
            ],
            'payment'            => [
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     * @return array
     * @throws Exception
     */
    protected function processPurchasePayloadWithProcessCaptchaAdvised(
        string $siteId = ProcessPurchaseBase::TESTING_SITE
    ): array {
        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale($siteId);

        $processPayload['payment']['ccNumber'] = $this->faker->creditCardNumber('MasterCard');

        return $processPayload;
    }

    /**
     * @param string $siteId Site id
     * @return array
     * @throws Exception
     */
    protected function processPurchasePayloadWithSpaces(string $siteId = self::TESTING_SITE): array
    {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@test.mindgeek.com',
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
                'ccNumber'            => $ccNumber,
                'cvv'                 => '951',
                'cardExpirationMonth' => '11',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     * @return array
     */
    protected function processPurchasePayloadNameWithNumbers(
        string $siteId = ProcessPurchaseBase::TESTING_SITE
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
     * @return array
     */
    protected function processPurchasePayloadWithMinimumMemberPayload(
        string $siteId = ProcessPurchaseBase::TESTING_SITE
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
     * @return string
     */
    protected function validInitPurchaseRequestUri(): string
    {
        return '/api/v1/purchase/init';
    }

    /**
     * @param string $xApiKey X Api Key
     * @return array
     */
    protected function initPurchaseHeaders(?string $xApiKey = null): array
    {
        return [
            'Content-Type' => 'application/json',
            'x-api-key'    => $xApiKey ?? $this->businessGroupTestingXApiKey()
        ];
    }

    /**
     * @return string
     */
    protected function validProcessPurchaseRequestUri(): string
    {
        return '/api/v1/purchase/process';
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
     * @param bool   $forceRocketgate Force rocketgate
     * @param string $siteId          Site id
     * @param null   $paymentMethod   Payment method; either null, mir, ccunionpay
     *
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithOneCrossSale(
        bool $forceRocketgate = false,
        string $siteId = ProcessPurchaseBase::TESTING_SITE,
        $paymentMethod = null
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->businessGroupTestingXApiKey();
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
        }

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayload($siteId, CurrencyCode::EUR, $paymentMethod),
            $headers
        );

        return $response;
    }

    /**
     * @param bool   $forceRocketgate Force rocketgate
     * @param string $siteId          Site id
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleAndExcessiveInitialDays(
        bool $forceRocketgate = false,
        string $siteId = ProcessPurchaseBase::TESTING_SITE
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->businessGroupTestingXApiKey();
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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


    /**
     * @param array $data            Existing Member Params
     * @param bool  $forceRocketgate Force rocketgate
     * @return ProcessPurchaseBase
     * @throws Exception
     */
    protected function initExistingMemberWithoutSubscriptionId(
        array $data = [],
        bool $forceRocketgate = false
    ): ProcessPurchaseBase {
        $headers = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @param array $data            Existing Member Params
     * @param bool  $forceRocketgate Force rocketgate
     * @return ProcessPurchaseBase
     * @throws Exception
     */
    protected function initExistingMemberWithSubscriptionId(
        array $data = [],
        bool $forceRocketgate = false
    ): ProcessPurchaseBase {
        $headers = $this->initPurchaseHeaders();

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @param bool $forceRocketgate Force rocketgate
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithTwoCrossSales(bool $forceRocketgate = false)
    {
        $initPayload = $this->initPurchasePayload();

        $initPayload['crossSellOptions'][1] = [
            'bundleId'     => self::BUNDLE_ID,
            'addonId'      => self::ADDON_ID,
            'siteId'       => self::TESTING_SITE,
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
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @throws Exception
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
     * @param bool $forceRocketgate Force rocketgate
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleWithoutTax(bool $forceRocketgate = false)
    {
        $initPurchasePayloadWithoutTax = $this->initPurchasePayload(self::TESTING_SITE);

        $headers = $this->initPurchaseHeaders($this->businessGroupTestingXApiKey());

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleWithoutRebill(bool $forceRocketgate = false)
    {
        $initPurchasePayloadWithoutRebill = $this->initPurchasePayload();

        $headers = $this->initPurchaseHeaders();

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithoutCrossSales(
        bool $forceRocketgate = false,
        string $siteId = ProcessPurchaseBase::TESTING_SITE
    ) {
        $initPurchasePayloadWithoutTax = $this->initPurchasePayload($siteId);

        unset($initPurchasePayloadWithoutTax['crossSellOptions']);

        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->businessGroupTestingXApiKey();
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @return mixed
     * @throws Exception
     */
    /**
     * @param bool $forceRocketgate Force rocketgate
     * @param bool $forceNetbilling Force netbilling
     *
     * @return ProcessPurchaseBase
     * @throws Exception
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

            $headers['X-Force-Cascade'] = 'test-rocketgate';
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
     * @param bool $forceRocketgate Force rocketgate
     * @return string
     * @throws \Exception
     * @throws Exception
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
     * @param string $sessionId     Session id
     * @param string $transactionId Transaction id
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
     * @param string      $transactionId   Transaction id
     * @param string|null $personalHashKey Personal hash key
     *
     * @return string[]
     */
    protected function postbackPayloadForQysso(string $transactionId, ?string $personalHashKey = null): array
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
            $personalHashKey ?? 'KI0ZQO62KP' // personalHashKey
        ];

        $signature = base64_encode(hash('sha256', implode("", $signatureData), true));

        $payload['signature'] = $signature;

        return $payload;
    }

    /**
     * @param bool   $forceNetbilling Force Netblling
     * @param string $siteId          Site id
     * @return mixed
     * @throws Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleForcedBillerAsNetbilling(
        bool $forceNetbilling = false,
        string $siteId = ProcessPurchaseBase::TESTING_SITE
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->businessGroupTestingXApiKey();
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
     * @return array
     * @throws Exception
     */
    protected function processPurchasePayloadWithNoSelectedCrossSaleForNetbillingBiller(
        string $siteId = self::TESTING_SITE
    ): array {
        $username = 'testPurchase' . random_int(100, 999);
        $ccNumber = $this->faker->creditCardNumber('MasterCard');

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@test.mindgeek.com',
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
                'ccNumber'            => $ccNumber,
                'cvv'                 => '887',
                'cardExpirationMonth' => '05',
                'cardExpirationYear'  => date('Y') + 1,
            ]
        ];
    }
}
