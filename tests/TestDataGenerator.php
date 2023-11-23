<?php

declare(strict_types=1);

namespace Tests;

use DateTime;
use DateTimeImmutable;
use Exception;
use Odesk\Phystrix\ApcStateStorage;
use Odesk\Phystrix\CommandFactory;
use Odesk\Phystrix\CircuitBreakerFactory;
use Odesk\Phystrix\CommandMetricsFactory;
use Odesk\Phystrix\RequestCache;
use Odesk\Phystrix\RequestLog;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataAccountInfoData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCard;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCrossSales;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataEnvironmentData;
use ProBillerNG\PurchaseGateway\Application\NuData\NuDataPurchasedProduct;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\BaseInitCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Services\BundleValidationService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCheckRetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionMember;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use ReflectionClass;
use ReflectionException;
use Tests\Unit\PurchaseGateway\Application\Services\SessionVersionConverterTest;
use Zend\Config\Config;
use Zend\Di\ServiceLocator;

trait TestDataGenerator
{
    use Faker;

    /**
     * Create init command
     *
     * @param array $data Data
     *
     * @return PurchaseInitCommand
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidDaysException
     */
    public function createInitCommand(array $data = []): PurchaseInitCommand
    {
        // We need to have same taxes after amount and amount
        $amount       = $data['amount'] ?? $this->faker->randomFloat(2, 1, 100);
        $rebillAmount = $data['rebillAmount'] ?? $this->faker->randomFloat(2, 1, 100);

        if (!isset($data['tax']['initialAmount']['afterTaxes'])) {
            $data['tax']['initialAmount']['afterTaxes'] = $amount;
        }

        if (!isset($data['tax']['rebillAmount']['afterTaxes'])) {
            $data['tax']['rebillAmount']['afterTaxes'] = $rebillAmount;
        }

        return new PurchaseInitCommand(
            $data['site'] ?? $this->createSite(),
            $data['amount'] ?? $amount,
            $data['initialDays'] ?? $this->faker->randomNumber(2),
            $data['rebillDays'] ?? $this->faker->randomNumber(2),
            $data['rebillAmount'] ?? $rebillAmount,
            $data['currency'] ?? 'USD',
            $data['bundleId'] ?? $this->faker->uuid,
            $data['addOnId'] ?? $this->faker->uuid,
            $data['clientIp'] ?? '127.0.0.1',
            $data['paymentType'] ?? 'cc',
            $data['clientCountryCode'] ?? 'CA',
            $data['sessionId'] ?? $this->faker->uuid,
            $data['atlasData'] ?? null,
            $data['atlasCode'] ?? null,
            $data['publicKeyIndex'] ?? $this->faker->randomNumber(5),
            $this->createTaxArray($data['tax'] ?? []),
            $data['crossSales'] ?? $this->createCrossSaleArray($data['crossSaleOptions'] ?? []),
            $data['isTrial'] ?? false,
            $data['memberId'] ?? null,
            $data['subscriptionId'] ?? $this->faker->uuid,
            $data['entrySiteId'] ?? null,
            $data['forceCascade'] ?? null,
            $data['paymentMethod'] ?? null,
            $data['trafficSource'] ?? null,
            $data['redirectUrl'] ?? null,
            $data['postbackUrl'] ?? null,
            [],
            false
        );
    }

    /**
     * @param array $data Data array
     *
     * @return ProcessPurchaseCommand
     * @throws Exception
     */
    public function createProcessCommand(array $data = []): ProcessPurchaseCommand
    {
        $string = 'abcdefghijklmnop1234';

        return new ProcessPurchaseCommand(
            $data['site'] ?? $this->createSite(),
            $data['username'] ?? $this->faker->shuffleString($string),
            $data['password'] ?? $this->faker->password,
            $data['email'] ?? $this->faker->email,
            $data['ccNumber'] ?? $this->faker->creditCardNumber('Visa'),
            $data['zip'] ?? $this->faker->postcode,
            $data['cvv'] ?? (string) $this->faker->numberBetween(100, 999),
            $data['expirationMonth'] ?? '05',
            $data['expirationYear'] ?? '2099',
            $data['firstName'] ?? $this->faker->firstName,
            $data['lastName'] ?? $this->faker->lastName,
            $data['address'] ?? $this->faker->address,
            $data['crossSales'] ?? [],
            $data['city'] ?? $this->faker->city,
            $data['state'] ?? $this->faker->stateAbbr,
            $data['country'] ?? $this->faker->countryCode,
            $data['phoneNumber'] ?? "+15498162066",
            $data['token'] ?? $this->faker->uuid,
            $data['sessionId'] ?? $this->faker->uuid,
            $data['requestUrl'] ?? 'api/v1/purchase/process',
            $data['userAgent'] ?? 'PostmanRuntime/7.22.0',
            $data['member'] ?? [],
            $data['payment'] ?? [],
            $data['lastFour'] ?? null,
            $data['paymentTemplateId'] ?? null,
            $data['ndWidgetData'] ?? null,
            $data['xForwardedFor'] ?? null,
            $data['paymentMethod'] ?? null,
            $data['routingNumber'] ?? null,
            $data['accountNumber'] ?? null
        );
    }

    /**
     * @param array $data Data
     *
     * @return array
     */
    public function createTaxArray(array $data = []): array
    {
        return [
            'initialAmount'    => [
                'beforeTaxes' => $data['initialAmount']['beforeTaxes'] ?? $this->faker->randomFloat(2, 1, 100),
                'taxes'       => $data['initialAmount']['taxes'] ?? $this->faker->randomFloat(2, 1, 100),
                'afterTaxes'  => $data['initialAmount']['afterTaxes'] ?? $this->faker->randomFloat(2, 1, 100),
            ],
            'rebillAmount'     => [
                'beforeTaxes' => $data['rebillAmount']['beforeTaxes'] ?? $this->faker->randomFloat(2, 1, 100),
                'taxes'       => $data['rebillAmount']['taxes'] ?? $this->faker->randomFloat(2, 1, 100),
                'afterTaxes'  => $data['rebillAmount']['afterTaxes'] ?? $this->faker->randomFloat(2, 1, 100),
            ],
            'taxApplicationId' => $data['taxApplicationId'] ?? $this->faker->uuid,
            'taxName'          => $data['taxName'] ?? 'testTaxName',
            'taxRate'          => $data['taxRate'] ?? 0.15,
            'taxType'          => $data['taxType'] ?? 'VAT',
            'custom'           => $data['custom'] ?? null
        ];
    }

    /**
     * @param array $data Array of cross sale data
     *
     * @return array
     */
    public function createCrossSaleArray(array $data = []): array
    {
        $crossSales = [];

        if (empty($data)) {
            $data = [0 => []];
        }

        foreach ($data as $key => $crossSaleData) {
            // We need to have same taxes after amount and amount
            $amount       = $crossSaleData['amount'] ?? $this->faker->randomFloat(2, 1, 100);
            $rebillAmount = $crossSaleData['rebillAmount'] ?? $this->faker->randomFloat(2, 1, 100);

            if (!isset($crossSaleData['tax']['initialAmount']['afterTaxes'])) {
                $crossSaleData['tax']['initialAmount']['afterTaxes'] = $amount;
            }

            if (!isset($crossSaleData['tax']['rebillAmount']['afterTaxes'])) {
                $crossSaleData['tax']['rebillAmount']['afterTaxes'] = $rebillAmount;
            }

            $crossSales[] = [
                'siteId'         => $crossSaleData['siteId'] ?? $this->faker->uuid,
                'subscriptionId' => $crossSaleData['subscriptionId'] ?? $this->faker->uuid,
                'amount'         => $amount,
                'initialDays'    => $crossSaleData['initialDays'] ?? $this->faker->randomNumber(2),
                'rebillDays'     => $crossSaleData['rebillDays'] ?? $this->faker->randomNumber(2),
                'rebillAmount'   => $rebillAmount,
                'bundleId'       => $crossSaleData['bundleId'] ?? $this->faker->uuid,
                'addonId'        => $crossSaleData['addonId'] ?? $this->faker->uuid,
                'tax'            => $this->createTaxArray($crossSaleData['tax'] ?? []),
                'isTrial'        => $crossSaleData['isTrial'] ?? false
            ];
        }

        return $crossSales;
    }

    /**
     * @return string
     */
    public function latestVersionSessionPayload(): string
    {
        return json_encode(SessionVersionConverterTest::$sessionLatestVersion);
    }

    /**
     * @param array $data Array of cross sale data
     *
     * @return array
     * @throws Exception
     */
    public function createPurchaseProcessedWithRocketgateNewPaymentEventData(array $data = []): array
    {
        $initialAmount = $this->faker->randomFloat(2, 1);
        $rebillAmount  = $this->faker->randomFloat(2, 1);
        $transactionId = $this->faker->uuid;

        $string         = 'abcdefghijklmnop1234';
        $dateOccurredOn = new DateTime();
        return [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => $dateOccurredOn->format('Y-m-d H:i:s.u'),
            'version'                  => 4,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => $data['transactionCollection'] ?? [
                    [
                        'state'         => $data['transactionCollection']['state'] ?? Transaction::STATUS_APPROVED,
                        'transactionId' => $transactionId,
                        'isNsf'         => null
                    ]
                ],
            'session_id'               => $this->faker->uuid,
            'site_id'                  => $this->faker->uuid,
            'skip_void_transaction'    => false,
            'status'                   => $data['success'] ?? 'success',
            'member_id'                => $this->faker->uuid,
            'subscription_id'          => $data['subscriptionId'] ?? $this->faker->uuid,
            'member_info'              => [
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
                'country'     => $this->faker->countryCode,
                'email'       => $this->faker->userName . '@test.mindgeek.com',
                'firstName'   => $this->faker->firstName,
                'ipAddress'   => $this->faker->ipv4,
                'lastName'    => $this->faker->lastName,
                'password'    => $this->faker->password,
                'phoneNumber' => $this->faker->phoneNumber,
                'username'    => $this->faker->shuffleString($string),
                'zipCode'     => (string) $this->faker->numberBetween(100000, 999999),
                'initCountry' => $this->faker->countryCode
            ],
            'is_existing_member'       => true,
            'cross_sale_purchase_data' => $data['crossSalePurchaseData'] ?? [
                    [
                        'itemId'                => $this->faker->uuid,
                        'bundleId'              => $this->faker->uuid,
                        'addonId'               => $this->faker->uuid,
                        'siteId'                => $this->faker->uuid,
                        'initialDays'           => $this->faker->numberBetween(1, 365),
                        'rebillDays'            => $this->faker->numberBetween(1, 365),
                        'initialAmount'         => $initialAmount,
                        'rebillAmount'          => $rebillAmount,
                        'transactionCollection' => $data['transactionCollectionCrossSale'] ?? [
                                [
                                    'state'         => $data['transactionCollectionCrossSale']['state'] ?? Transaction::STATUS_APPROVED,
                                    'transactionId' => $transactionId,
                                    'isNsf'         => null
                                ]
                            ],
                        'isTrial'               => false,
                        'isCrossSale'           => true,
                        'subscriptionId'        => $this->faker->uuid,
                        'tax'                   => [
                            'initialAmount'    => [
                                'beforeTaxes' => $this->faker->randomFloat(2, 1),
                                'taxes'       => $this->faker->randomFloat(2, 1),
                                'afterTaxes'  => $initialAmount,
                            ],
                            'rebillAmount'     => [
                                'beforeTaxes' => $this->faker->randomFloat(2, 1),
                                'taxes'       => $this->faker->randomFloat(2, 1),
                                'afterTaxes'  => $rebillAmount,
                            ],
                            'taxApplicationId' => $this->faker->uuid,
                            'taxName'          => 'VAT',
                            'taxRate'          => $this->faker->randomFloat(2, 1),
                        ],
                    ]
                ],
            'payment'                  => [
                'ccNumber'        => '*******',
                'cvv'             => '*******',
                'expirationMonth' => $this->faker->numberBetween(1, 12),
                'expirationYear'  => $this->faker->numberBetween(2022, 2030)
            ],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'three_d_required'         => false,
            'is_third_party'           => false,
            'subscription_username'    => $this->faker->shuffleString($string),
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => $this->faker->numberBetween(1, 365),
            'initial_days'             => $this->faker->numberBetween(1, 365),
            'atlas_code'               => '',
            'atlas_data'               => '',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => $data['is_trial'] ?? false,
            'amount'                   => $this->faker->randomFloat(2, 1),
            'rebill_amount'            => $this->faker->randomFloat(2, 1),
            'amounts'                  => isset($data['tax']) ? $data['tax'] : ([
                'initialAmount'    => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $initialAmount,
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $rebillAmount,
                ],
                'taxApplicationId' => $this->faker->uuid,
                'taxName'          => 'VAT',
                'taxRate'          => $this->faker->randomFloat(2, 1),
            ]),
            'attempt_biller'           => [
                'submitNumber' => 1,
                'biller'       => [
                    'ROCKETGATE'    => 'rocketgate',
                    'NETBILLING'    => 'netbilling',
                    'ROCKETGATE_ID' => '23423',
                    'NETBILLING_ID' => '23424'
                ]
            ],
            'is_username_padded'       => false,
            'is_imported_by_api'       => false

        ];
    }


    /**
     * @param array $data Array of cross sale data with padded username  in main purchase and secondary revenue
     *
     * @return array
     * @throws Exception
     */
    public function createPurchaseProcessedWithExistingUsernameAndCrossaleWithRocketgateNewPaymentEventData(array $data = []): array
    {
        $initialAmount = $this->faker->randomFloat(2, 1);
        $rebillAmount  = $this->faker->randomFloat(2, 1);
        $transactionId = $this->faker->uuid;

        $string         = 'abcdefghijklmnop1234';
        $dateOccurredOn = new DateTime();
        return [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => $dateOccurredOn->format('Y-m-d H:i:s.u'),
            'version'                  => 4,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => $data['transactionCollection'] ?? [
                    [
                        'state'         => $data['transactionCollection']['state'] ?? Transaction::STATUS_APPROVED,
                        'transactionId' => $transactionId,
                        'newCCUsed'     => true,
                        'isNsf'         => false,
                        'threeDFrictionless' => false
                    ]
                ],
            'session_id'               => $this->faker->uuid,
            'site_id'                  => $this->faker->uuid,
            'skip_void_transaction'    => false,
            'status'                   => $data['success'] ?? 'success',
            'member_id'                => $this->faker->uuid,
            'subscription_id'          => $data['subscriptionId'] ?? $this->faker->uuid,
            'member_info'              => [
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
                'country'     => $this->faker->countryCode,
                'email'       => $this->faker->userName . '@test.mindgeek.com',
                'firstName'   => $this->faker->firstName,
                'ipAddress'   => $this->faker->ipv4,
                'lastName'    => $this->faker->lastName,
                'password'    => $this->faker->password,
                'phoneNumber' => $this->faker->phoneNumber,
                'username'    => $this->faker->shuffleString($string),
                'zipCode'     => (string) $this->faker->numberBetween(100000, 999999),
                'initCountry' => $this->faker->countryCode
            ],
            'is_existing_member'       => true,
            'cross_sale_purchase_data' => $data['crossSalePurchaseData'] ?? [
                    [
                        'itemId'                => $this->faker->uuid,
                        'bundleId'              => $this->faker->uuid,
                        'addonId'               => $this->faker->uuid,
                        'siteId'                => $this->faker->uuid,
                        'initialDays'           => $this->faker->numberBetween(1, 365),
                        'rebillDays'            => $this->faker->numberBetween(1, 365),
                        'initialAmount'         => $initialAmount,
                        'rebillAmount'          => $rebillAmount,
                        'transactionCollection' => $data['transactionCollectionCrossSale'] ?? [
                                [
                                    'state'         => $data['transactionCollectionCrossSale']['state'] ?? Transaction::STATUS_APPROVED,
                                    'transactionId' => $transactionId,
                                    'isNsf'         => false,
                                    'newCCUsed'     => true,
                                ]
                            ],
                        'isTrial'               => false,
                        'isCrossSale'           => true,
                        'isCrossSaleSelected'   => true,
                        'subscriptionId'        => $this->faker->uuid,
                        'isUsernamePadded'      =>true,
                        'subscriptionUsername'  => $this->faker->shuffleString($string),
                        'subscriptionPassword'  => $this->faker->password,
                        'tax'                   => [
                            'initialAmount'    => [
                                'beforeTaxes' => $this->faker->randomFloat(2, 1),
                                'taxes'       => $this->faker->randomFloat(2, 1),
                                'afterTaxes'  => $initialAmount,
                            ],
                            'rebillAmount'     => [
                                'beforeTaxes' => $this->faker->randomFloat(2, 1),
                                'taxes'       => $this->faker->randomFloat(2, 1),
                                'afterTaxes'  => $rebillAmount,
                            ],
                            'taxApplicationId' => $this->faker->uuid,
                            'taxName'          => 'VAT',
                            'taxRate'          => $this->faker->randomFloat(2, 1),
                        ],
                    ]
                ],
            'payment'                  => [
                'ccNumber'        => '*******',
                'cvv'             => '*******',
                'expirationMonth' => $this->faker->numberBetween(1, 12),
                'expirationYear'  => $this->faker->numberBetween(2022, 2030)
            ],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'three_d_required'         => false,
            'is_third_party'           => false,
            'subscription_username'    => $this->faker->shuffleString($string),
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => $this->faker->numberBetween(1, 365),
            'initial_days'             => $this->faker->numberBetween(1, 365),
            'atlas_code'               => '',
            'atlas_data'               => '',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => $data['is_trial'] ?? false,
            'amount'                   => $this->faker->randomFloat(2, 1),
            'rebill_amount'            => $this->faker->randomFloat(2, 1),
            'amounts'                  => isset($data['tax']) ? $data['tax'] : ([
                'initialAmount'    => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $initialAmount,
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $rebillAmount,
                ],
                'taxApplicationId' => $this->faker->uuid,
                'taxName'          => 'VAT',
                'taxRate'          => $this->faker->randomFloat(2, 1),
            ]),
            'attempt_biller'           => [
                'submitNumber' => 1,
                'biller'       => [
                    'ROCKETGATE'    => 'rocketgate',
                    'NETBILLING'    => 'netbilling',
                    'ROCKETGATE_ID' => '23423',
                    'NETBILLING_ID' => '23424'
                ]
            ],
            'is_username_padded'       => true,
            'is_imported_by_api'       => true

        ];
    }

    /**
     * @return array
     */
    public function createCurlLegacyImportReponse(): array
    {
        $response = '{
                          "0": {
                            "member": {
                              "member_id": 237111559,
                              "member_uuid": "1d5c2fa1-e3ba-4381-953b-787f1bb20618",
                              "email": "testPurchase998@test.mindgeek.com",
                              "phone_number": "5140000911",
                              "first_name": "Mister",
                              "last_name": "Axe",
                              "address": "123 Random Street Hello Boulevard",
                              "city": "Montreal",
                              "state": "CA",
                              "zip": "h1h1h1",
                              "country": "CA",
                              "username": "",
                              "password": ""
                            },
                            "subscription": {
                              "_subscriptionId": 287924381,
                              "_username": "testPurchase998",
                              "_password": "***",
                              "_isTrial": 0,
                              "_siteId": "3483",
                              "_authSystemId": "103",
                              "_authSystemSiteId": "318",
                              "_initialDays": "5",
                              "_memberId": "237111559",
                              "_joinDate": {
                                "date": "2021-04-13 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_initialPayment": 29.99,
                              "_affiliateTrackingCode": "NDU1MDk1OjQ4OjE0Nw",
                              "_expiryDate": {
                                "date": "2021-04-18 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_parentSubscriptionId": null,
                              "_requireActiveParent": "1",
                              "_isProbiller": 0,
                              "_disabled": 0,
                              "_syncFromAuthSystem": 2,
                              "subscriptionUUID": "94d7e655-64ba-48cb-b4b0-ddf0c95680be",
                              "_syncFromBiller": true,
                              "_billerName": "rocketgate",
                              "_recurringId": 222652673
                            },
                            "transaction": {
                              "_transactionId": 1213270893,
                              "_productId": 68943,
                              "_billerAccountId": 3273,
                              "_type": "sale",
                              "_issueDate": {
                                "date": "2021-04-13 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_amount": 29.99,
                              "_status": "OK",
                              "_statusDate": {
                                "date": "2021-04-13 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_isThreeDSecured": false,
                              "_custom": "Import NG Transaction",
                              "_currencyId": "71",
                              "_requestSiteId": "3483",
                              "_originId": 1,
                              "_paymentTypeId": "3",
                              "_billerName": "Rocketgate",
                              "_memberId": "237111559",
                              "_paymentTemplate": "VALID",
                              "_subscriptionId": 287924381
                            },
                            "recurringChargeOnly": null,
                            "purchasedBundle": {
                              "bundleId": "4475820e-2956-11e9-b210-d663bd873d93",
                              "itemId": "20faafb4-a590-470c-abc2-22594f260f71",
                              "purchaseId": "c366ca60-6ac9-4c3d-93e1-b6498449a873",
                              "memberId": "1d5c2fa1-e3ba-4381-953b-787f1bb20618",
                              "subscriptionId": "94d7e655-64ba-48cb-b4b0-ddf0c95680be",
                              "transactionId": 1213270893,
                              "selectedAddOns": "[]",
                              "initialProperties": "{\"isTrial\":false,\"isUnlimited\":false,\"isDisabled\":false,\"isExpired\":false,\"isNSF\":null,\"isPrepaid\":false,\"isLowRisk\":false,\"requireActiveContent\":\"1\",\"entrySiteId\":\"3483\",\"rebillingDays\":null,\"initialDays\":null,\"creationDate\":null}",
                              "recurringChargeOnlyId": null
                            },
                            "status": "success",
                            "itemId": "20faafb4-a590-470c-abc2-22594f260f71",
                            "transactionId": "ac70776c-b172-4887-ae76-c23eef46540a"
                          },
                          "1": {
                            "member": {
                              "member_id": "237111559",
                              "member_uuid": "1d5c2fa1-e3ba-4381-953b-787f1bb20618",
                              "email": "testPurchase998@test.mindgeek.com",
                              "phone_number": "5140000911",
                              "first_name": "Mister",
                              "last_name": "Axe",
                              "address": "123 Random Street Hello Boulevard",
                              "city": "Montreal",
                              "state": "CA",
                              "zip": "h1h1h1",
                              "country": "CA",
                              "username": "",
                              "password": ""
                            },
                            "subscription": {
                              "_subscriptionId": 287924382,
                              "_username": "testPurchase998",
                              "_password": "***",
                              "_isTrial": 0,
                              "_siteId": "3873",
                              "_authSystemId": "103",
                              "_authSystemSiteId": "447",
                              "_initialDays": "3",
                              "_memberId": "237111559",
                              "_joinDate": {
                                "date": "2021-04-13 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_initialPayment": 1,
                              "_affiliateTrackingCode": "NDU1MDk1OjQ4OjE0Nw",
                              "_expiryDate": {
                                "date": "2021-04-16 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_parentSubscriptionId": 287924381,
                              "_requireActiveParent": "1",
                              "_isProbiller": 0,
                              "_disabled": 0,
                              "_syncFromAuthSystem": 2,
                              "subscriptionUUID": "0fec4495-6000-4ae2-a236-73680aec00a6",
                              "_syncFromBiller": true,
                              "_billerName": "rocketgate",
                              "_recurringId": 222652674
                            },
                            "transaction": {
                              "_transactionId": 1213270894,
                              "_productId": 91713,
                              "_billerAccountId": 3273,
                              "_type": "sale",
                              "_issueDate": {
                                "date": "2021-04-13 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_amount": 1,
                              "_status": "OK",
                              "_statusDate": {
                                "date": "2021-04-13 00:00:00.000000",
                                "timezone_type": 3,
                                "timezone": "UTC"
                              },
                              "_isThreeDSecured": false,
                              "_custom": "Import NG Transaction",
                              "_currencyId": "71",
                              "_requestSiteId": "3873",
                              "_originId": 1,
                              "_paymentTypeId": "3",
                              "_billerName": "Rocketgate",
                              "_memberId": "237111559",
                              "_paymentTemplate": "VALID",
                              "_subscriptionId": 287924382
                            },
                            "recurringChargeOnly": null,
                            "purchasedBundle": {
                              "bundleId": "4475820e-2956-11e9-b210-d663bd873d93",
                              "itemId": "cdd16ef4-f2f4-4f5c-8a90-d0d63ab05dae",
                              "purchaseId": "c366ca60-6ac9-4c3d-93e1-b6498449a873",
                              "memberId": "1d5c2fa1-e3ba-4381-953b-787f1bb20618",
                              "subscriptionId": "0fec4495-6000-4ae2-a236-73680aec00a6",
                              "transactionId": 1213270894,
                              "selectedAddOns": "[]",
                              "initialProperties": "{\"isTrial\":false,\"isUnlimited\":false,\"isDisabled\":false,\"isExpired\":false,\"isNSF\":null,\"isPrepaid\":false,\"isLowRisk\":false,\"requireActiveContent\":\"1\",\"entrySiteId\":\"3873\",\"rebillingDays\":null,\"initialDays\":null,\"creationDate\":null}",
                              "recurringChargeOnlyId": null
                            },
                            "status": "success",
                            "itemId": "cdd16ef4-f2f4-4f5c-8a90-d0d63ab05dae",
                            "transactionId": "06a961e6-ead9-4be6-b168-c4786ab62ad0"
                          },
                          "time": 1618358913.610256,
                          "code": 0,
                          "message": "Operation successful."
                        }';

        return json_decode($response, true);
    }

    /**
     * @return string
     */
    public static function getUSACountryCode(): string
    {
        return "US";
    }

    /**
     * @param array $data Array of cross sale data
     *
     * @return array
     */
    public function createPurchaseProcessedWithRocketgateNewPaymentEventDataWithoutCrossSale(array $data = []): array
    {
        $initialAmount = $this->faker->randomFloat(2, 1);
        $rebillAmount  = $this->faker->randomFloat(2, 1);
        $transactionId = $this->faker->uuid;

        $string         = 'abcdefghijklmnop1234';
        $dateOccurredOn = new DateTime();
        return [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => $dateOccurredOn->format('Y-m-d H:i:s.u'),
            'version'                  => 4,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => $data['transactionCollection'] ?? [
                    [
                        'state'         => $data['transactionCollection']['state'] ?? Transaction::STATUS_APPROVED,
                        'transactionId' => $transactionId,
                        'isNsf'         => null
                    ]
                ],
            'session_id'               => $this->faker->uuid,
            'site_id'                  => $this->faker->uuid,
            'status'                   => $data['success'] ?? 'success',
            'member_id'                => $this->faker->uuid,
            'subscription_id'          => $data['subscriptionId'] ?? $this->faker->uuid,
            'member_info'              => [
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
                'country'     => self::getUSACountryCode(),
                'email'       => $this->faker->userName . '@test.mindgeek.com',
                'firstName'   => $this->faker->firstName,
                'ipAddress'   => $this->faker->ipv4,
                'lastName'    => $this->faker->lastName,
                'password'    => $this->faker->password,
                'phoneNumber' => $this->faker->phoneNumber,
                'username'    => $this->faker->shuffleString($string),
                'zipCode'     => (string) $this->faker->numberBetween(100000, 999999),
                'initCountry' => $this->faker->countryCode
            ],
            'is_existing_member'       => true,
            'cross_sale_purchase_data' => $data['crossSalePurchaseData'] ?? [

                ],
            'payment'                  => [
                'ccNumber'        => '*******',
                'cvv'             => '*******',
                'expirationMonth' => $this->faker->numberBetween(1, 12),
                'expirationYear'  => $this->faker->numberBetween(2022, 2030)
            ],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'three_d_required'         => false,
            'is_third_party'           => false,
            'subscription_username'    => $this->faker->shuffleString($string),
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => $this->faker->numberBetween(1, 365),
            'initial_days'             => $this->faker->numberBetween(1, 365),
            'atlas_code'               => '',
            'atlas_data'               => '',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => $data['is_trial'] ?? false,
            'amount'                   => $this->faker->randomFloat(2, 1),
            'rebill_amount'            => $this->faker->randomFloat(2, 1),
            'amounts'                  => isset($data['tax']) ? $data['tax'] : ([
                'initialAmount'    => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $initialAmount,
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $rebillAmount,
                ],
                'taxApplicationId' => $this->faker->uuid,
                'taxName'          => 'VAT',
                'taxRate'          => $this->faker->randomFloat(2, 1),
            ]),
            'attempt_biller'           => [
                'submitNumber' => 1,
                'biller'       => [
                    'ROCKETGATE'    => 'rocketgate',
                    'NETBILLING'    => 'netbilling',
                    'ROCKETGATE_ID' => '23423',
                    'NETBILLING_ID' => '23424'
                ]
            ],
            'is_username_padded'       => false,
            'skip_void_transaction'    => false,
        ];
    }

    /**
     * @param array $data Array of cross sale data
     *
     * @return array
     */
    public function createPurchaseProcessedNewPaymentWithEpochEventData(array $data = []): array
    {
        $initialAmount = $this->faker->randomFloat(2, 1);
        $rebillAmount  = $this->faker->randomFloat(2, 1);

        $string = 'abcdefghijklmnop1234';

        return [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => $this->faker->dateTime->format('Y-m-d H:i:s.u'),
            'version'                  => 4,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => $data['transactionCollection'] ?? [
                    [
                        'state'         => $data['transactionCollectionCrossSale']['state'] ?? Transaction::STATUS_APPROVED,
                        'transactionId' => $this->faker->uuid,
                        'newCCUsed'     => $data['newCCUsed'] ?? true,
                        'billerName'    => EpochBiller::BILLER_NAME
                    ]
                ],
            'session_id'               => $this->faker->uuid,
            'site_id'                  => $this->faker->uuid,
            'status'                   => $data['success'] ?? 'success',
            'member_id'                => $this->faker->uuid,
            'skip_void_transaction'    => false,
            'subscription_id'          => $data['subscriptionId'] ?? $this->faker->uuid,
            'member_info'              => [
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
                'country'     => $this->faker->countryCode,
                'email'       => $this->faker->userName . '@test.mindgeek.com',
                'firstName'   => $this->faker->firstName,
                'ipAddress'   => $this->faker->ipv4,
                'lastName'    => $this->faker->lastName,
                'password'    => $this->faker->password,
                'phoneNumber' => $this->faker->phoneNumber,
                'username'    => $this->faker->shuffleString($string),
                'zipCode'     => (string) $this->faker->numberBetween(100000, 999999),
                'initCountry' => $this->faker->countryCode
            ],
            'is_existing_member'       => false,
            'cross_sale_purchase_data' => $data['crossSalePurchaseData'] ?? [
                    [
                        'itemId'                => $this->faker->uuid,
                        'bundleId'              => $this->faker->uuid,
                        'addonId'               => $this->faker->uuid,
                        'siteId'                => $this->faker->uuid,
                        'initialDays'           => $this->faker->numberBetween(1, 365),
                        'rebillDays'            => $this->faker->numberBetween(1, 365),
                        'initialAmount'         => $initialAmount,
                        'rebillAmount'          => $rebillAmount,
                        'transactionCollection' => $data['transactionCollectionCrossSale'] ?? [
                                [
                                    'state'         => $data['transactionCollectionCrossSale']['state'] ?? Transaction::STATUS_APPROVED,
                                    'transactionId' => $this->faker->uuid,
                                    'newCCUsed'     => $data['newCCUsed'] ?? true,
                                    'billerName'    => EpochBiller::BILLER_NAME
                                ]
                            ],
                        'isTrial'               => false,
                        'isCrossSale'           => true,
                        'subscriptionId'        => $this->faker->uuid,
                        'tax'                   => [
                            'initialAmount'    => [
                                'beforeTaxes' => $this->faker->randomFloat(2, 1),
                                'taxes'       => $this->faker->randomFloat(2, 1),
                                'afterTaxes'  => $initialAmount,
                            ],
                            'rebillAmount'     => [
                                'beforeTaxes' => $this->faker->randomFloat(2, 1),
                                'taxes'       => $this->faker->randomFloat(2, 1),
                                'afterTaxes'  => $rebillAmount,
                            ],
                            'taxApplicationId' => $this->faker->uuid,
                            'taxName'          => 'VAT',
                            'taxRate'          => $this->faker->randomFloat(2, 1),
                        ],
                    ]
                ],
            'payment'                  => [],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'three_d_required'         => false,
            'is_third_party'           => false,
            'subscription_username'    => $this->faker->shuffleString($string),
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => $this->faker->numberBetween(1, 365),
            'initial_days'             => $this->faker->numberBetween(1, 365),
            'atlas_code'               => '',
            'atlas_data'               => '',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => $data['is_trial'] ?? false,
            'amount'                   => $this->faker->randomFloat(2, 1),
            'rebill_amount'            => $this->faker->randomFloat(2, 1),
            'amounts'                  => isset($data['tax']) ? $data['tax'] : (
            [
                'initialAmount'    => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $initialAmount,
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $rebillAmount,
                ],
                'taxApplicationId' => $this->faker->uuid,
                'taxName'          => 'VAT',
                'taxRate'          => $this->faker->randomFloat(2, 1),
            ]
            ),
            'is_username_padded'       => false
        ];
    }

    /**
     * @param array $data Array of cross sale data
     *
     * @return array
     */
    public function createPurchaseProcessedWithQyssoEventData(array $data = []): array
    {
        $string = 'abcdefghijklmnop1234';

        return [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => $this->faker->dateTime->format('Y-m-d H:i:s.u'),
            'version'                  => 12,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => $data['transactionCollection'] ?? [
                    [
                        'state'               => $data['mainTransaction']['state'] ?? Transaction::STATUS_APPROVED,
                        'transactionId'       => $this->faker->uuid,
                        'newCCUsed'           => null,
                        'billerName'          => 'qysso',
                        'acs'                 => '',
                        'pareq'               => '',
                        'redirectUrl'         => 'https:\/\/process.qysso.com\/member\/remoteCharge_Back.asp?TransID=810&CompanyNum=' . $_ENV['QYSSO_COMPANY_NUM'],
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
            'session_id'               => $this->faker->uuid,
            'site_id'                  => $this->faker->uuid,
            'status'                   => $data['success'] ?? 'success',
            'member_id'                => $this->faker->uuid,
            'subscription_id'          => $this->faker->uuid,
            'entry_site_id'            => null,
            'member_info'              => [
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
                'country'     => $this->faker->countryCode,
                'email'       => $this->faker->userName . '@test.mindgeek.com',
                'firstName'   => $this->faker->firstName,
                'ipAddress'   => $this->faker->ipv4,
                'lastName'    => $this->faker->lastName,
                'password'    => $this->faker->password,
                'phoneNumber' => $this->faker->phoneNumber,
                'username'    => $this->faker->shuffleString($string),
                'zipCode'     => (string) $this->faker->numberBetween(100000, 999999),
                'initCountry' => $this->faker->countryCode
            ],
            'cross_sale_purchase_data' => [],
            'payment'                  => [
                'paymentType'   => 'banktransfer',
                'paymentMethod' => 'zelle'
            ],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'subscription_username'    => $this->faker->shuffleString($string),
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => $this->faker->numberBetween(1, 365),
            'initial_days'             => $this->faker->numberBetween(1, 365),
            'atlas_code'               => '',
            'atlas_data'               => '',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => $data['is_trial'] ?? false,
            'amount'                   => $this->faker->randomFloat(2, 1),
            'rebill_amount'            => $this->faker->randomFloat(2, 1),
            'amounts'                  => null,
            'is_existing_member'       => $data['is_existing_member'] ?? false,
            'three_drequired'          => false,
            'is_third_party'           => true,
            'is_nsf'                   => false,
            'skip_void_transaction'    => false,
            'payment_method'           => 'zelle',
            'traffic_source'           => 'ALL',
            'threed_version'           => null,
            'threed_frictionless'      => false,
            'is_username_padded'       => false
        ];
    }

    /**
     * @param array $data Array of cross sale data
     * @return array
     */
    public function createPurchaseProcessedExistingPaymentEventData(array $data = []): array
    {
        $initialAmount = $this->faker->randomFloat(2, 1);
        $rebillAmount  = $this->faker->randomFloat(2, 1);
        $transactionId = $this->faker->uuid;
        $string        = 'abcdefghijklmnop1234';

        return [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => $this->faker->dateTime->format('Y-m-d H:i:s.u'),
            'version'                  => 4,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => $data['transactionCollection'] ?? [
                    [
                        'state'         => $data['transactionCollection']['state'] ?? Transaction::STATUS_APPROVED,
                        'transactionId' => $transactionId
                    ]
                ],
            'session_id'               => $this->faker->uuid,
            'site_id'                  => $this->faker->uuid,
            'status'                   => $data['success'] ?? 'success',
            'member_id'                => $this->faker->uuid,
            'subscription_id'          => $data['subscriptionId'] ?? $this->faker->uuid,
            'entry_site_id'            => $data['entrySiteId'] ?? $this->faker->uuid,
            'cross_sale_purchase_data' => [
                [
                    'itemId'                => $this->faker->uuid,
                    'bundleId'              => $this->faker->uuid,
                    'addonId'               => $this->faker->uuid,
                    'siteId'                => $this->faker->uuid,
                    'initialDays'           => $this->faker->numberBetween(1, 365),
                    'rebillDays'            => $this->faker->numberBetween(1, 365),
                    'initialAmount'         => $initialAmount,
                    'rebillAmount'          => $rebillAmount,
                    'transactionCollection' => $data['transactionCollectionCrossSale'] ?? [
                            [
                                'state'         => $data['transactionCollectionCrossSale']['state'] ?? Transaction::STATUS_APPROVED,
                                'transactionId' => $transactionId,
                                'isNsf'         => null
                            ]
                        ],
                    'isTrial'               => false,
                    'isCrossSale'           => true,
                    'subscriptionId'        => $this->faker->uuid,
                    'tax'                   => [
                        'initialAmount'    => [
                            'beforeTaxes' => $this->faker->randomFloat(2, 1),
                            'taxes'       => $this->faker->randomFloat(2, 1),
                            'afterTaxes'  => $initialAmount,
                        ],
                        'rebillAmount'     => [
                            'beforeTaxes' => $this->faker->randomFloat(2, 1),
                            'taxes'       => $this->faker->randomFloat(2, 1),
                            'afterTaxes'  => $rebillAmount,
                        ],
                        'taxApplicationId' => $this->faker->uuid,
                        'taxName'          => 'VAT',
                        'taxRate'          => $this->faker->randomFloat(2, 1),
                    ],
                ]
            ],
            'payment'                  => [
                'cardHash'          => '*******',
                'paymentTemplateId' => $data['paymentTemplateId'] ?? $this->faker->uuid,
            ],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'three_d_required'         => false,
            'is_third_party'           => false,
            'subscription_username'    => $this->faker->shuffleString($string),
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => $this->faker->numberBetween(1, 365),
            'initial_days'             => $this->faker->numberBetween(1, 365),
            'atlas_code'               => '',
            'atlas_data'               => '',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => $data['is_trial'] ?? false,
            'amount'                   => $this->faker->randomFloat(2, 1),
            'rebill_amount'            => $this->faker->randomFloat(2, 1),
            'amounts'                  => [
                'initialAmount'    => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $initialAmount,
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => $this->faker->randomFloat(2, 1),
                    'taxes'       => $this->faker->randomFloat(2, 1),
                    'afterTaxes'  => $rebillAmount,
                ],
                'taxApplicationId' => $this->faker->uuid,
                'taxName'          => 'VAT',
                'taxRate'          => $this->faker->randomFloat(2, 1),
            ],
            'is_existing_member'       => true,
            'is_username_padded'       => false
        ];
    }

    /**
     * @param array $dataForInitCommand Init Request
     *
     * @return PurchaseProcess
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     * @throws ReflectionException
     */
    public function createPurchaseProcess($dataForInitCommand = []): PurchaseProcess
    {
        $reflection = new ReflectionClass(BaseInitCommandHandler::class);

        $createProcessPurchaseMethod = $reflection->getMethod('initPurchaseProcess');
        $createProcessPurchaseMethod->setAccessible(true);
        $initHandler = $this->createMock(BaseInitCommandHandler::class);

        $bundleValidationServiceProperty = $reflection->getProperty('bundleValidationService');
        $bundleValidationServiceProperty->setAccessible(true);
        $bundleValidationServiceProperty->setValue(
            $initHandler,
            $this->createMock(BundleValidationService::class)
        );

        $createProcessPurchaseMethod->invoke($initHandler, $this->createInitCommand($dataForInitCommand));

        $attribute = $reflection->getProperty('purchaseProcess');
        $attribute->setAccessible(true);

        return $attribute->getValue($initHandler);
    }

    /**
     * @param bool $isStickyGateway Is Sticky Gateway
     * @param bool $isNsfSupported  Is Nsf supported
     *
     * @param null $serviceCollection
     *
     * @return Site
     * @throws Exception
     */
    public function createSite($isStickyGateway = false, $isNsfSupported = false, ServiceCollection $serviceCollection = null)
    {
        return Site::create(
            SiteId::create(),
            BusinessGroupId::create(),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            '',
            '',
            '',
            '',
            '',
            $serviceCollection ?? $this->createServiceCollection(),
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $this->createPublicKeyCollection(),
            'Business group descriptor',
            $isStickyGateway,
            $isNsfSupported,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );
    }

    /**
     * @return UserInfo
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws InvalidZipCodeException
     */
    public function createUserInfo()
    {
        $payload = [
            'country'        => CountryCode::create($this->faker->countryCode),
            'ipAddress'      => Ip::create($this->faker->ipv4),
            'username'       => Username::create('test1234'),
            'password'       => Password::create('pass12345'),
            'firstName'      => FirstName::create($this->faker->firstName),
            'lastName'       => LastName::create($this->faker->lastName),
            'email'          => Email::create($this->faker->email),
            'zipCode'        => Zip::create('80085'),
            'city'           => $this->faker->city,
            'state'          => $this->faker->city,
            'phoneNumber'    => PhoneNumber::create('514-000-0911'),
            'address'        => $this->faker->address,
            'billerMemberId' => '113927096852'
        ];

        return UserInfo::create(
            $payload['country'],
            $payload['ipAddress'],
            $payload['email'],
            $payload['username'],
            $payload['password'],
            $payload['firstName'],
            $payload['lastName'],
            $payload['zipCode'],
            $payload['city'],
            $payload['state'],
            $payload['phoneNumber'],
            $payload['address']
        );
    }

    /**
     * @return ServiceCollection
     */
    private function createServiceCollection(): ServiceCollection
    {
        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(Service::create('Service name', true));

        return $serviceCollection;
    }

    /**
     * @return PublicKeyCollection
     * @throws Exception
     */
    private function createPublicKeyCollection(): PublicKeyCollection
    {
        $publicKeyCollection = new PublicKeyCollection();

        $publicKeyCollection->add(
            PublicKey::create(
                KeyId::createFromString('3dcc4a19-e2a8-4622-8e03-52247bbd302d'),
                DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2019-11-15 16:11:41.0000')
            )
        );

        return $publicKeyCollection;
    }

    /**
     * @param string|null $merchantId          The merchant id
     * @param string|null $invoiceId           The invoice id
     * @param string|null $customerId          The customer id
     * @param array       $billerTransactions  The biller transactions array
     * @param bool        $securedWithThreeD   The 3ds flag
     * @param int|null    $cardExpirationMonth Exp month
     * @param int|null    $cardExpirationYear  Exp year
     * @param int|null    $threedsVersion      Threeds version
     *
     * @return RocketgateCCRetrieveTransactionResult
     * @throws Exception
     */
    protected function createRocketgateCCRetrieveTransactionResultMocks(
        string $merchantId = null,
        string $invoiceId = null,
        string $customerId = null,
        array $billerTransactions = [],
        bool $securedWithThreeD = false,
        int $cardExpirationMonth = null,
        int $cardExpirationYear = null,
        int $threedsVersion = null
    ): RocketgateCCRetrieveTransactionResult {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn('23423');
        $retrieveTransaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getSiteId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getMerchantId')->willReturn($merchantId ?? $this->faker->uuid);
        $retrieveTransaction->method('getInvoiceId')->willReturn($invoiceId ?? $this->faker->uuid);
        $retrieveTransaction->method('getCustomerId')->willReturn($customerId ?? $this->faker->uuid);
        $retrieveTransaction->method('getCurrency')->willReturn('USD');
        $retrieveTransaction->method('getPaymentType')->willReturn('cc');
        $retrieveTransaction->method('getMerchantPassword')->willReturn('password');
        $retrieveTransaction->method('getCardHash')->willReturn('123456789');
        $retrieveTransaction->method('getMerchantAccount')->willReturn('MerchantAccount');
        $retrieveTransaction->method('getCardDescription')->willReturn('CREDIT');
        $retrieveTransaction->method('getBillerTransactions')->willReturn($billerTransactions);
        $retrieveTransaction->method('getSecuredWithThreeD')->willReturn($securedWithThreeD);
        $retrieveTransaction->method('getThreedSecuredVersion')->willReturn($threedsVersion);

        $memberInformation = $this->createMock(MemberInformation::class);
        $memberInformation->method('email')->willReturn('email@email.com');
        $memberInformation->method('phoneNumber')->willReturn('5558887777');
        $memberInformation->method('firstName')->willReturn('Dorel');
        $memberInformation->method('lastName')->willReturn('Popescu');
        $memberInformation->method('address')->willReturn('Address');
        $memberInformation->method('city')->willReturn('Galati');
        $memberInformation->method('state')->willReturn('Galati');
        $memberInformation->method('zip')->willReturn('800081');
        $memberInformation->method('country')->willReturn('Romania');

        $ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $ccTransactionInformation->method('first6')->willReturn('123456');
        $ccTransactionInformation->method('last4')->willReturn('4444');
        $ccTransactionInformation->method('transactionId')->willReturn($this->getIdFor('transactionId'));
        $ccTransactionInformation->method('amount')->willReturn(29.99);
        $ccTransactionInformation->method('createdAt')->willReturn(new DateTimeImmutable('25.04.2019'));
        $ccTransactionInformation->method('rebillAmount')->willReturn(15.99);
        $ccTransactionInformation->method('rebillFrequency')->willReturn(5);
        $ccTransactionInformation->method('rebillStart')->willReturn(1);
        $ccTransactionInformation->method('cardExpirationYear')->willReturn($cardExpirationYear);
        $ccTransactionInformation->method('cardExpirationMonth')->willReturn($cardExpirationMonth);

        $billerFields = RocketgateBillerFields::create(
            $merchantId ?? $this->faker->uuid,
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'],
            $this->faker->uuid,
            'sharedSecret',
            true
        );

        return new RocketgateCCRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            $billerFields
        );
    }

    /**
     * @param string|null $merchantId         Merchant id
     * @param string|null $invoiceId          Invoice id
     * @param string|null $customerId         Customer id
     * @param array       $billerTransactions Biller transactions
     *
     * @return RocketgateCheckRetrieveTransactionResult
     * @throws Exception
     */
    protected function createRocketgateCheckRetrieveTransactionResultMocks(
        string $merchantId = null,
        string $invoiceId = null,
        string $customerId = null,
        array $billerTransactions = []
    ): RocketgateCheckRetrieveTransactionResult {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn('23423');
        $retrieveTransaction->method('getTransactionId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getSiteId')->willReturn($this->faker->uuid);
        $retrieveTransaction->method('getMerchantId')->willReturn($merchantId ?? $this->faker->uuid);
        $retrieveTransaction->method('getInvoiceId')->willReturn($invoiceId ?? $this->faker->uuid);
        $retrieveTransaction->method('getCustomerId')->willReturn($customerId ?? $this->faker->uuid);
        $retrieveTransaction->method('getCurrency')->willReturn('USD');
        $retrieveTransaction->method('getPaymentType')->willReturn(ChequePaymentInfo::PAYMENT_TYPE);
        $retrieveTransaction->method('getMerchantPassword')->willReturn('password');
        $retrieveTransaction->method('getCardHash')->willReturn('123456789');
        $retrieveTransaction->method('getMerchantAccount')->willReturn('MerchantAccount');
        $retrieveTransaction->method('getBillerTransactions')->willReturn($billerTransactions);
        $retrieveTransaction->method('getSecuredWithThreeD')->willReturn(false);
        $retrieveTransaction->method('getThreedSecuredVersion')->willReturn(null);

        $memberInformation = $this->createMock(MemberInformation::class);
        $memberInformation->method('email')->willReturn('email@email.com');
        $memberInformation->method('phoneNumber')->willReturn('5558887777');
        $memberInformation->method('firstName')->willReturn('Dorel');
        $memberInformation->method('lastName')->willReturn('Popescu');
        $memberInformation->method('address')->willReturn('Address');
        $memberInformation->method('city')->willReturn('Galati');
        $memberInformation->method('state')->willReturn('Galati');
        $memberInformation->method('zip')->willReturn('800081');
        $memberInformation->method('country')->willReturn('Romania');

        $ccTransactionInformation = $this->createMock(CheckTransactionInformation::class);
        $ccTransactionInformation->method('transactionId')->willReturn($this->getIdFor('transactionId'));
        $ccTransactionInformation->method('amount')->willReturn(29.99);
        $ccTransactionInformation->method('createdAt')->willReturn(new DateTimeImmutable('25.04.2019'));
        $ccTransactionInformation->method('rebillAmount')->willReturn(15.99);
        $ccTransactionInformation->method('rebillFrequency')->willReturn(5);
        $ccTransactionInformation->method('rebillStart')->willReturn(1);

        $billerFields = RocketgateBillerFields::create(
            $merchantId ?? $this->faker->uuid,
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'],
            $this->faker->uuid,
            'sharedSecret',
            true
        );

        return new RocketgateCheckRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            $billerFields
        );
    }

    /**
     * @param string $key string
     *
     * @return mixed
     */
    private function getIdFor(string $key): ?string
    {
        $ids = [
            'transactionId'  => '3e68f6fd-3841-4682-bd6e-9efdebd172af',
            'purchaseId'     => '3e68f6fd-3841-4682-bd6e-9efdebd172af',
            'bundleId'       => '785f8be9-0cb9-4d58-ad98-feef5138a26f',
            'addonId'        => '10ebbf8c-7a2b-4f2e-b80a-b92e8ecd0c9b',
            'memberId'       => 'fc2ab1c7-fe93-423f-b7fb-b8fe97bf1ec0',
            'subscriptionId' => '66d1efaf-6516-4ed6-bc22-469afab57d42',
            'siteId'         => 'da3450e2-72d2-47cb-9215-95244fd083af',
            'itemId'         => 'da3450e2-72d2-47cb-9215-95244fd083bc'
        ];

        return $ids[$key] ?? null;
    }

    /**
     * @return NuDataSettings
     */
    public function createNuDataSettings(): NuDataSettings
    {
        return NuDataSettings::create(
            'w-123456',
            'https://api-mgk.nd.nudatasecurity.com/health/',
            true
        );
    }

    /**
     * @return NuDataEnvironmentData
     */
    public function createNuDataEnvironmentData(): NuDataEnvironmentData
    {
        return new NuDataEnvironmentData(
            $this->faker->uuid,
            '{"ndWidgetData": "widget"}',
            '10.10.109.185',
            '/api/v1/purchase/process',
            'PostmanRuntime/7.22.0',
            '192.168.16.1'
        );
    }

    /**
     * @return NuDataPurchasedProduct
     */
    public function createNuDataPurchasedProduct(): NuDataPurchasedProduct
    {
        return new NuDataPurchasedProduct(
            10,
            $this->faker->uuid,
            true,
            $this->faker->uuid,
            true,
            true
        );
    }

    /**
     * @return NuDataCard
     */
    public function createNuDataCard(): NuDataCard
    {
        return new NuDataCard(
            'Mister Axe',
            $_ENV['ROCKETGATE_COMMON_CARD_NUMBER']
        );
    }

    /**
     * @return NuDataAccountInfoData
     */
    public function createNuDataAccountInfoData(): NuDataAccountInfoData
    {
        return new NuDataAccountInfoData(
            'username',
            'password',
            'email@mindgeek.com',
            'Mister',
            'Axe',
            '514-000-0911',
            '123 Random Street Hello Boulevard',
            'Montreal',
            null,
            'CA',
            'h1h1h1'
        );
    }

    /**
     * @return NuDataCrossSales
     */
    public function createNuDataCrossSales(): NuDataCrossSales
    {
        $crossSales = new NuDataCrossSales();
        $crossSales->addProduct($this->createNuDataPurchasedProduct());

        return $crossSales;
    }


    /**
     * @return BillerCollection
     * @throws \ProBillerNG\Logger\Exception
     * @throws UnknownBillerNameException
     */
    public function createBillerCollection(): BillerCollection
    {
        return BillerCollection::buildBillerCollection(
            [
                new RocketgateBiller(),
                new NetbillingBiller()
            ]
        );
    }

    /**
     * @param array $overrides Config overrides
     *
     * @return CommandFactory
     */
    protected function getCircuitBreakerCommandFactory(array $overrides = []): CommandFactory
    {
        $configArgs = app('config')->get("phystrix");
        $configArgs = array_merge($configArgs, $overrides);
        $config     = new Config($configArgs);

        $stateStorage          = new ApcStateStorage();
        $circuitBreakerFactory = new CircuitBreakerFactory($stateStorage);
        $commandMetricsFactory = new CommandMetricsFactory($stateStorage);

        return new CommandFactory(
            $config,
            new ServiceLocator(),
            $circuitBreakerFactory,
            $commandMetricsFactory,
            new RequestCache(),
            new RequestLog()
        );
    }

    /**
     * @param array|null $data Data to overwrite
     *
     * @return array
     */
    public function createSessionInfo(?array $data): array
    {
        return [
            'atlasFields'                   => [
                'atlasCode' => 'NDU1MDk1OjQ4OjE0Nw',
                'atlasData' => 'atlas data example',
            ],
            'cascade'                       => [
                'currentBiller'         => 'rocketgate',
                'billers'               => [
                    'rocketgate',
                    'netbilling'
                ],
                'currentBillerSubmit'   => 1,
                'currentBillerPosition' => 0
            ],
            'fraudAdvice'                   => [
                'ip'                      => '10.10.109.185',
                'email'                   => '',
                'zip'                     => '',
                'bin'                     => '',
                'initCaptchaAdvised'      => false,
                'initCaptchaValidated'    => false,
                'processCaptchaAdvised'   => false,
                'processCaptchaValidated' => false,
                'blacklistedOnInit'       => false,
                'blacklistedOnProcess'    => false,
                'captchaAlreadyValidated' => false,
                'timesBlacklisted'        => 0,
                'forceThreeD'             => false,
                'forceThreeDOnInit'       => false,
                'forceThreeDOnProcess'    => false,
                'detectThreeDUsage'       => false,
            ],
            'nuDataSettings'                => [
                'clientId' => 'w-123456',
                'url'      => 'https://api-mgk.nd.nudatasecurity.com/health/',
                'enabled'  => true
            ],
            'initializedItemCollection'     => [
                0 => [
                    'itemId'                => $this->faker->uuid,
                    'addonId'               => '670af402-2956-11e9-b210-d663bd873d93',
                    'bundleId'              => '5fd44440-2956-11e9-b210-d663bd873d93',
                    'siteId'                => '8e34c94e-135f-4acb-9141-58b3a6e56c74',
                    'initialDays'           => 365,
                    'rebillDays'            => 365,
                    'initialAmount'         => 14.97,
                    'rebillAmount'          => 11.2,
                    'taxes'                 => [
                        'initialAmount'    => [
                            'beforeTaxes' => 11,
                            'taxes'       => 11,
                            'afterTaxes'  => 14.97,
                        ],
                        'rebillAmount'     => [
                            'beforeTaxes' => 11,
                            'taxes'       => 11,
                            'afterTaxes'  => 11.2,
                        ],
                        'taxRate'          => 0.05,
                        'taxName'          => 'HST',
                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                        'taxType'          => 'sales'
                    ],
                    'transactionCollection' => $data['transactionCollection'] ?? [],
                    'isTrial'               => false,
                    'isCrossSale'           => false,
                    'isCrossSaleSelected'   => false
                ],
                1 => [
                    'itemId'                => $this->faker->uuid,
                    'addonId'               => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
                    'bundleId'              => '4475820e-2956-11e9-b210-d663bd873d93',
                    'siteId'                => '4c22fba2-f883-11e8-8eb2-f2801f1b9fd1',
                    'initialDays'           => 2,
                    'rebillDays'            => 30,
                    'initialAmount'         => 98.6,
                    'rebillAmount'          => 92,
                    'taxes'                 => [
                        'initialAmount'    => [
                            'beforeTaxes' => 11,
                            'taxes'       => 11,
                            'afterTaxes'  => 98.6,
                        ],
                        'rebillAmount'     => [
                            'beforeTaxes' => 11,
                            'taxes'       => 11,
                            'afterTaxes'  => 92,
                        ],
                        'taxRate'          => 0.05,
                        'taxName'          => 'HST',
                        'taxApplicationId' => '60bf5bcb-ac64-496c-acc5-9c7cf54a1869',
                        'taxType'          => 'sales'
                    ],
                    'transactionCollection' => $data['transactionCollectionCrossSale'] ?? [],
                    'isTrial'               => false,
                    'isCrossSale'           => true,
                    'isCrossSaleSelected'   => false
                ],
            ],
            'paymentType'                   => 'cc',
            'publicKeyIndex'                => 1,
            'sessionId'                     => 'c1d8feba-a8a6-47f0-8292-7a191c8448a1',
            'state'                         => $data['status'] ?? 'valid',
            'userInfo'                      => [
                'address'     => null,
                'city'        => null,
                'country'     => 'CA',
                'email'       => '',
                'firstName'   => '',
                'ipAddress'   => '10.10.109.185',
                'lastName'    => '',
                'password'    => '',
                'phoneNumber' => '',
                'state'       => null,
                'username'    => '',
                'zipCode'     => '',
            ],
            'gatewaySubmitNumber'           => 0,
            'isExpired'                     => false,
            'memberId'                      => null,
            'purchaseId'                    => null,
            'subscriptionId'                => null,
            'entrySiteId'                   => null,
            'paymentTemplateCollection'     => null,
            'existingMember'                => false,
            'currency'                      => 'USD',
            'redirectUrl'                   => null,
            'postbackUrl'                   => null,
            'paymentMethod'                 => null,
            'trafficSource'                 => 'ALL',
            'fraudRecommendationCollection' => [
                [
                    'severity' => 'Allow',
                    'code'     => 1000,
                    'message'  => 'Allow_Transaction',
                ]
            ],
            'paymentTemplateId'             => null,
            'creditCardWasBlacklisted'      => false
        ];
    }

    /**
     * @param string $paymentType
     *
     * @return RetrieveTransaction
     */
    public function createRetrieveRocketgateTransaction(
        string $paymentType = CCPaymentInfo::PAYMENT_TYPE
    ): RetrieveTransaction {
        $retrieveTransactionMember = new RetrieveTransactionMember(
            [
                "email"   => $this->faker->uuid,
                "name"    => $this->faker->name,
                "zip"     => '123',
                "country" => $this->faker->countryCode
            ]
        );

        $transaction = new RetrieveTransactionTransaction(
            [
                "transactionId"       => "0f83faea-165f-4f49-ad3b-83ea1e0bab50",
                "amount"              => "14.97",
                "createdAt"           => "2020-05-28 11:03:58",
                "rebillAmount"        => "10",
                "rebillFrequency"     => "365",
                "rebillStart"         => "30",
                "status"              => "approved",
                "first6"              => "",
                "last4"               => "",
                "cardExpirationYear"  => "",
                "cardExpirationMonth" => ""
            ]
        );

        $rocketgateBillerFields = [
            "merchant_id"          => "1234567",
            "merchant_password"    => "password",
            "merchant_customer_id" => "f42811ab-5ffd009d9b7174.92072099",
            "merchant_invoice_id"  => "62d6ea21-5ffd009d9b7288.55661922",
            "merchant_account"     => "",
            "ip_address"           => "10.10.109.185",
            "merchant_site_id"     => "12345",
            "sharedSecret"         => "sharedSecret",
            "simplified3DS"        => true,
            "merchant_product_id"  => "",
            "merchant_descriptor"  => ""
        ];

        $billerTransactions = new RetrieveTransactionBillerTransactions(
            [
                'type'                => 'sale',
                'invoiceId'           => '62d6ea21-5ffd009d9b7288.55661922',
                'customerId'          => 'f42811ab-5ffd009d9b7174.92072099',
                'billerTransactionId' => 'billerTransactionId'
            ]
        );

        return new RetrieveTransaction(
            [
                'billerId'              => RocketgateBiller::BILLER_ID,
                'merchantId'            => null,
                'merchantPassword'      => null,
                'invoiceId'             => null,
                'customerId'            => null,
                'cardHash'              => null,
                'transactionId'         => $this->faker->uuid,
                'billerTransactionId'   => null,
                'billerMemberId'        => null,
                'currency'              => $this->faker->currencyCode,
                'siteId'                => $this->faker->uuid,
                'paymentType'           => $paymentType,
                'paymentMethod'         => 'visa',
                'merchantAccount'       => null,
                'cardExpirationYear'    => '2025',
                'cardExpirationMonth'   => '12',
                'cardDescription'       => 'cardDescription',
                'member'                => $retrieveTransactionMember,
                'transaction'           => $transaction,
                'billerSettings'        => $rocketgateBillerFields,
                'billerTransactions'    => [$billerTransactions],
                'securedWithThreeD'     => null,
                'previousTransactionId' => null,
                'binRouting'            => null
            ]
        );
    }

    /**
     * @param string $paymentType Payment Type
     *
     * @return RetrieveTransaction
     */
    public function createRetrieveEpochTransaction(
        string $paymentType = CCPaymentInfo::PAYMENT_TYPE
    ): RetrieveTransaction {
        $retrieveTransactionMember = new RetrieveTransactionMember(
            [
                "email"   => $this->faker->uuid,
                "name"    => $this->faker->name,
                "zip"     => '123',
                "country" => $this->faker->countryCode
            ]
        );

        $transaction = new RetrieveTransactionTransaction(
            [
                "transactionId"       => "0f83faea-165f-4f49-ad3b-83ea1e0bab50",
                "amount"              => "14.97",
                "createdAt"           => "2020-05-28 11:03:58",
                "rebillAmount"        => "10",
                "rebillFrequency"     => "365",
                "rebillStart"         => "30",
                "status"              => "approved",
                "first6"              => "",
                "last4"               => "",
                "cardExpirationYear"  => "",
                "cardExpirationMonth" => ""
            ]
        );

        $epochBillerFields = //new EpochBillerFields(
            [
                "clientId"              => "111",
                "clientKey"             => "clientKey",
                "clientVerificationKey" => "clientVerificationKey",
                "siteName"              => "www.brazzers.com",
                "redirectUrl"           => "http://purchase-gateway.probiller.com/api/v1/purchase/thirdParty/return/{jwt}",
                "notificationUrl"       => "https://postback-gateway.probiller.com/api/postbacks/{UUID}",
                "invoiceId"             => "UUID"
            ];
        //);

        $billerTransactions = new RetrieveTransactionBillerTransactions(
            [
                'piCode'              => 'piCode',
                'billerMemberId'      => 'billerMemberId',
                'ans'                 => 'ans',
                'billerTransactionId' => 'billerTransactionId'
            ]
        );

        return new RetrieveTransaction(
            [
                'billerId'              => EpochBiller::BILLER_ID,
                'merchantId'            => null,
                'merchantPassword'      => null,
                'invoiceId'             => null,
                'customerId'            => null,
                'cardHash'              => null,
                'transactionId'         => $this->faker->uuid,
                'billerTransactionId'   => null,
                'billerMemberId'        => null,
                'currency'              => $this->faker->currencyCode,
                'siteId'                => $this->faker->uuid,
                'paymentType'           => $paymentType,
                'paymentMethod'         => 'visa',
                'merchantAccount'       => null,
                'cardExpirationYear'    => '2025',
                'cardExpirationMonth'   => '12',
                'cardDescription'       => 'cardDescription',
                'member'                => $retrieveTransactionMember,
                'transaction'           => $transaction,
                'billerSettings'        => $epochBillerFields,
                'billerTransactions'    => [$billerTransactions],
                'securedWithThreeD'     => null,
                'previousTransactionId' => null,
                'binRouting'            => null
            ]
        );
    }
}
