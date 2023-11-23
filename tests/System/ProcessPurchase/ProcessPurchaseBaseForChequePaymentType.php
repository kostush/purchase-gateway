<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase;

abstract class ProcessPurchaseBaseForChequePaymentType extends ProcessPurchaseBase
{
    /**
     * @param bool   $forceRocketgate Force rocketgate
     * @param string $siteId          Site id
     * @return mixed
     * @throws \Exception
     */
    protected function initPurchaseProcessWithOneCrossSaleForChequePurchase(
        bool $forceRocketgate = true,
        string $siteId = ProcessPurchaseBase::REALITY_KINGS_SITE_ID
    ) {
        $xApiKey = (self::REALITY_KINGS_SITE_ID === $siteId) ? $this->paysitesXApiKey() : $this->tubesXApiKey();
        $headers = $this->initPurchaseHeaders($xApiKey);

        if ($forceRocketgate) {
            $headers['X-Force-Cascade'] = 'test-rocketgate';
        }

        $response = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $this->initPurchasePayloadForChequePurchase($siteId),
            $headers
        );

        return $response;
    }

    /**
     * @param string $siteId SiteId
     * @return array
     * @throws \Exception
     */
    public function initPurchasePayloadForChequePurchase(string $siteId = self::REALITY_KINGS_SITE_ID): array
    {
        $bundle = $this->createAndAddBundleToRepository(
            [
                'bundleId' => self::BUNDLE_ID,
                'addonId'  => self::ADDON_ID,
            ]
        );

        return [
            'siteId'            => $siteId,
            'bundleId'          => (string) $bundle->bundleId(),
            'addonId'           => (string) $bundle->addonId(),
            'currency'          => 'USD',
            'clientIp'          => '10.10.109.185',
            'paymentType'       => 'checks',
            'paymentMethod'       => 'checks',
            'clientCountryCode' => 'US',
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
            ]
        ];
    }

    /**
     * @param string $siteId Site id
     *
     * @return array
     * @throws \Exception
     */
    protected function processPurchasePayloadWithNoSelectedCrossSaleForChequePaymentType(
        string $siteId = self::REALITY_KINGS_SITE_ID
    ): array {
        $username = 'testPurchase' . random_int(100, 999);

        return [
            'siteId'  => $siteId,
            'member'  => [
                'email'       => $username . '@EPS.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'US',
                'zipCode'     => '89141',
                'address1'    => '123 Main St',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Las Vegas',
                'state'       => 'NV',
                'phone'       => '514-000-0911',
            ],
            'payment' => [
                "checkInformation" => [
                    "routingNumber"       => "999999999",
                    "accountNumber"       => "112233",
                    "savingAccount"       => false,
                    "socialSecurityLast4" => "5233",
                    "label"               => "testLabel"
                ]
            ]
        ];
    }
}