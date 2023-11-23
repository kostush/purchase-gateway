<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\CascadeBillersExhausted;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Created;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use Tests\UnitTestCase;

class PurchaseProcessTest extends UnitTestCase
{
    const UUID = '11973b24-b381-11e9-a2a3-2a2ae2dbcce4';

    /**
     * @test
     * @return PurchaseProcess
     * @throws \ProBillerNG\Logger\Exception
     */
    public function create_should_return_a_purchase_process_object(): PurchaseProcess
    {
        $itemsCollection = new InitializedItemCollection();
        $itemsCollection->offsetSet(self::UUID, $this->createMock(InitializedItem::class));

        $result = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(CCPaymentInfo::class),
            new InitializedItemCollection(),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            'ALL'
        );

        $this->assertInstanceOf(PurchaseProcess::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function the_purchase_process_object_should_have_a_created_state(PurchaseProcess $purchaseProcess): void
    {
        $this->assertInstanceOf(Created::class, $purchaseProcess->state());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function the_purchase_process_object_should_contain_a_session_id(PurchaseProcess $purchaseProcess): void
    {
        $this->assertInstanceOf(SessionId::class, $purchaseProcess->sessionId());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function the_purchase_process_object_should_contain_an_item_collection(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertInstanceOf(InitializedItemCollection::class, $purchaseProcess->initializedItemCollection());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function the_purchase_process_object_should_contain_an_atlas_fields_object(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertInstanceOf(AtlasFields::class, $purchaseProcess->atlasFields());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function the_purchase_process_object_should_have_a_currency(PurchaseProcess $purchaseProcess): void
    {
        $this->assertInstanceOf(CurrencyCode::class, $purchaseProcess->currency());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function set_cascade_should_add_the_cascade_object_to_the_purchase_process_object(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->setCascade(
            $this->createMock(Cascade::class)
        );

        $this->assertInstanceOf(Cascade::class, $purchaseProcess->cascade());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function set_fraud_advice_should_add_the_fraud_advice_object_to_the_purchase_process_object(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->setFraudAdvice(
            $this->createMock(FraudAdvice::class)
        );

        $this->assertInstanceOf(FraudAdvice::class, $purchaseProcess->fraudAdvice());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function set_nu_data_settings_should_add_the_nu_data_settings_object_to_the_purchase_process_object(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->setNuDataSettings(
            $this->createMock(NuDataSettings::class)
        );

        $this->assertInstanceOf(NuDataSettings::class, $purchaseProcess->nuDataSettings());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function set_payment_template_adds_the_payment_template_collection_object_to_the_purchase_process_object(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->setPaymentTemplateCollection($this->createMock(PaymentTemplateCollection::class));

        $this->assertInstanceOf(PaymentTemplateCollection::class, $purchaseProcess->paymentTemplateCollection());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function was_member_id_generated_should_return_false(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertFalse($purchaseProcess->wasMemberIdGenerated());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return PurchaseProcess
     * @throws \Exception
     */
    public function build_member_id_should_return_a_valid_member_id_object(
        PurchaseProcess $purchaseProcess
    ): PurchaseProcess {
        $this->assertInstanceOf(MemberId::class, $purchaseProcess->buildMemberId());

        return $purchaseProcess;
    }

    /**
     * @test
     * @depends build_member_id_should_return_a_valid_member_id_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function was_member_id_generated_should_return_false_after_build_member_is_called_and_member_id_was_sent_into_construct(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertFalse($purchaseProcess->wasMemberIdGenerated());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws \Exception
     */
    public function build_member_id_should_return_the_same_uuid_as_member_id_method(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertSame($purchaseProcess->memberId(), (string) $purchaseProcess->buildMemberId());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws \Exception
     */
    public function build_purchase_id_should_return_a_valid_purchase_id_object(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertInstanceOf(PurchaseId::class, $purchaseProcess->buildPurchaseId());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws \Exception
     */
    public function build_purchase_id_should_return_the_same_uuid_as_purchase_id_method(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertSame($purchaseProcess->purchaseId(), (string) $purchaseProcess->buildPurchaseId());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws \Exception
     */
    public function is_existing_member_purchase_method_should_return_true_if_member_id_is_set(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertSame(!empty($purchaseProcess->memberId()), $purchaseProcess->isExistingMemberPurchase());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_set_member_id_as_null_when_reset_member_id(): void
    {
        $purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(CCPaymentInfo::class),
            new InitializedItemCollection(),
            null,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            'ALL'
        );

        $purchaseProcess->resetMemberId();

        $this->assertNull($purchaseProcess->memberId());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function to_array_method_should_return_contain_all_defined_keys($object): void
    {
        $keys = [
            'version',
            'atlasFields',
            'billerMapping',
            'binRouting',
            'cascade',
            'fraudAdvice',
            'nuDataSettings',
            'fraudRecommendationCollection',
            'initializedItemCollection',
            'paymentType',
            'publicKeyIndex',
            'sessionId',
            'state',
            'userInfo',
            'gatewaySubmitNumber',
            'isExpired',
            'memberId',
            'subscriptionId',
            'entrySiteId',
            'paymentTemplateCollection',
            'existingMember',
            'currency',
            'redirectUrl',
            'postbackUrl',
            'paymentMethod',
            'trafficSource',
            'purchaseId',
            'skipVoid',
            'paymentTemplateId',
            'creditCardWasBlacklisted'
        ];

        $testValid = true;

        foreach ($object->toArray() as $key => $value) {
            if (!in_array($key, $keys)) {
                $testValid = false;
                break;
            }
        }

        $this->assertTrue($testValid);
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return InitializedItem
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function initialize_item_should_add_correct_item_structure_to_the_collection(
        PurchaseProcess $purchaseProcess
    ): InitializedItem {
        $item = [
            'siteId'         => '8e34c94e-135f-4acb-9141-58b3a6e56c74',
            'bundleId'       => '5fd44440-2956-11e9-b210-d663bd873d93',
            'addonId'        => '670af402-2956-11e9-b210-d663bd873d93',
            'subscriptionId' => '670af402-2956-11e9-b210-d663bd873d93',
            'amount'         => 29.99,
            'initialDays'    => 365,
            'rebillDays'     => 365,
            'rebillAmount'   => 29.99,
            'isTrial'        => false,
            'tax'            => [
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
                'taxName'          => 'HST',
                'taxRate'          => 0.05,
                'custom'           => 'customData',
                'taxType'          => 'sales'
            ],
        ];
        $purchaseProcess->initializeItem($item);

        $expected                        = $item;
        $expected['isCrossSale']         = false;
        $expected['isCrossSaleSelected'] = false;
        $expected['initialAmount']       = $item['amount'];
        $expected['isNSFSupported']      = false;

        unset($expected['amount']);

        // Add autogenerated data to expected array
        $expected['itemId']                = (string) $purchaseProcess->initializedItemCollection()->first()->itemId();
        $expected['transactionCollection'] = [];

        $this->assertEquals(
            $expected,
            $purchaseProcess->initializedItemCollection()->first()->toArray()
        );

        return $purchaseProcess->initializedItemCollection()->first();
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return InitializedItem
     * @throws Exception\ItemCouldNotBeRestoredException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function restore_item_should_add_correct_item_structure_to_the_collection(
        PurchaseProcess $purchaseProcess
    ): InitializedItem {
        $transactionId = $this->faker->uuid;

        $item = [
            'itemId'                => $this->faker->uuid,
            'siteId'                => $this->faker->uuid,
            'bundleId'              => $this->faker->uuid,
            'addonId'               => $this->faker->uuid,
            'subscriptionId'        => $this->faker->uuid,
            'amount'                => 29.99,
            'initialDays'           => 365,
            'rebillDays'            => 365,
            'rebillAmount'          => 29.99,
            'isTrial'               => false,
            'tax'                   => [
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
                'taxName'          => 'HST',
                'taxRate'          => 0.05,
                'custom'           => 'customData',
                'taxType'          => 'sales'
            ],
            'transactionCollection' => [
                [
                    'state'               => 'aborted',
                    'transactionId'       => '',
                    'billerName'          => 'rocketgate',
                    'newCCUsed'           => true,
                    'acs'                 => 'simulatedAcs',
                    'pareq'               => 'simulatedPareq',
                    'redirectUrl'         => '',
                    'isNsf'               => null,
                    'deviceCollectionUrl' => null,
                    'deviceCollectionJwt' => null,
                    'deviceFingerprintId' => null,
                    'threeDStepUpUrl'     => null,
                    'threeDStepUpJwt'     => null,
                    'md'                  => null,
                    'threeDFrictionless'  => false,
                    'threeDVersion'       => null,
                    'crossSales'          => null,
                ],
                [
                    'state'               => 'approved',
                    'transactionId'       => $transactionId,
                    'billerName'          => 'rocketgate',
                    'newCCUsed'           => true,
                    'acs'                 => 'simulatedAcs',
                    'pareq'               => 'simulatedPareq',
                    'redirectUrl'         => '',
                    'isNsf'               => null,
                    'deviceCollectionUrl' => null,
                    'deviceCollectionJwt' => null,
                    'deviceFingerprintId' => null,
                    'threeDStepUpUrl'     => null,
                    'threeDStepUpJwt'     => null,
                    'md'                  => null,
                    'threeDFrictionless'  => false,
                    'threeDVersion'       => null,
                    'crossSales'          => null,
                ],
            ],
            'isCrossSaleSelected'   => false
        ];
        $purchaseProcess->restoreItem($item);

        $restoredItem = $purchaseProcess->initializedItemCollection()->offsetGet($item['itemId']);

        $this->assertInstanceOf(InitializedItem::class, $restoredItem);

        return $restoredItem;
    }

    /**
     * @test
     * @depends restore_item_should_add_correct_item_structure_to_the_collection
     * @param InitializedItem $restoredItem The restored item
     * @return void
     */
    public function restored_item_should_have_the_correct_number_of_transactions(InitializedItem $restoredItem): void
    {
        $this->assertCount(2, $restoredItem->transactionCollection());
    }

    /**
     * @test
     * @depends restore_item_should_add_correct_item_structure_to_the_collection
     * @param InitializedItem $restoredItem The restored item
     * @return void
     */
    public function restored_item_should_not_have_new_transaction_ids_generated(InitializedItem $restoredItem)
    {
        /** @var Transaction $transaction */
        $transaction = $restoredItem->transactionCollection()->first();
        $this->assertNull($transaction->transactionId());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws Exception\ItemCouldNotBeRestoredException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function restore_item_will_throw_exception_for_invalid_item(
        PurchaseProcess $purchaseProcess
    ): void {

        $this->expectException(Exception\ItemCouldNotBeRestoredException::class);

        $item = [
            // missing itemId
            'siteId'         => $this->faker->uuid,
            'bundleId'       => $this->faker->uuid,
            'addonId'        => $this->faker->uuid,
            'subscriptionId' => $this->faker->uuid,
            'amount'         => 29.99,
            'initialDays'    => 365,
            'rebillDays'     => 365,
            'rebillAmount'   => 29.99,
            'isTrial'        => false,
            'tax'            => [
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
                'taxName'          => 'HST',
                'taxRate'          => 0.05,
                'custom'           => 'customData',
                'taxType'          => 'sales'
            ],
        ];
        $purchaseProcess->restoreItem($item);
    }

    /**
     * @test
     * @depends initialize_item_should_add_correct_item_structure_to_the_collection
     * @param InitializedItem $initializedItem Initialized Item
     * @return void
     */
    public function initialize_item_should_add_item_with_correct_charge_information_model(
        InitializedItem $initializedItem
    ): void {

        $this->assertInstanceOf(
            BundleRebillChargeInformation::class,
            $initializedItem->chargeInformation()
        );
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws Exception\InvalidUserInfoEmail
     * @throws Exception\InvalidZipCodeException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws Exception\ValidationException
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \Throwable
     */
    public function restore_should_return_a_purchase_process_object(): PurchaseProcess
    {
        $sessionInfo = [
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
                'currentBillerSubmit'   => 0,
                'currentBillerPosition' => 0,
                'removedBillersFor3DS'  => []
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
            'fraudRecommendationCollection' => [
                0 => [
                    "severity" => "Allow",
                    "code"     => 1000,
                    "message"  => "Allow_Transaction"
                ]
            ],
            'nuDataSettings'                => [
                'clientId' => 'w-123456',
                'url'      => 'https://api-mgk.nd.nudatasecurity.com/health/',
                'enabled'  => true
            ],
            'initializedItemCollection'     => [
                0 => [
                    'itemId'              => $this->faker->uuid,
                    'addonId'             => '670af402-2956-11e9-b210-d663bd873d93',
                    'bundleId'            => '5fd44440-2956-11e9-b210-d663bd873d93',
                    'siteId'              => '8e34c94e-135f-4acb-9141-58b3a6e56c74',
                    'initialDays'         => 365,
                    'rebillDays'          => 365,
                    'initialAmount'       => 14.97,
                    'rebillAmount'        => 11.2,
                    'taxes'               => [
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
                    'isTrial'             => false,
                    'isCrossSale'         => false,
                    'isCrossSaleSelected' => false
                ],
                1 => [
                    'itemId'              => $this->faker->uuid,
                    'addonId'             => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
                    'bundleId'            => '4475820e-2956-11e9-b210-d663bd873d93',
                    'siteId'              => '4c22fba2-f883-11e8-8eb2-f2801f1b9fd1',
                    'initialDays'         => 2,
                    'rebillDays'          => 30,
                    'initialAmount'       => 98.6,
                    'rebillAmount'        => 92,
                    'taxes'               => [
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
                    'isTrial'             => false,
                    'isCrossSale'         => true,
                    'isCrossSaleSelected' => false
                ],
            ],
            'paymentType'                   => 'cc',
            'publicKeyIndex'                => 1,
            'sessionId'                     => 'c1d8feba-a8a6-47f0-8292-7a191c8448a1',
            'state'                         => 'valid',
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
            'paymentTemplateId'             => null,
            'creditCardWasBlacklisted'      => false
        ];

        $purchaseProcess = PurchaseProcess::restore($sessionInfo);

        $this->assertInstanceOf(PurchaseProcess::class, $purchaseProcess);

        return $purchaseProcess;
    }

    /**
     * @test
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     * @depends restore_should_return_a_purchase_process_object
     */
    public function it_should_contain_nu_data_settings_object_after_restore(PurchaseProcess $purchaseProcess): void
    {
        $this->assertInstanceOf(NuDataSettings::class, $purchaseProcess->nuDataSettings());
    }

    /**
     * @test
     * @depends restore_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function it_should_have_a_cascade(PurchaseProcess $purchaseProcess): void
    {
        $this->assertInstanceOf(Cascade::class, $purchaseProcess->cascade());
    }

    /**
     * @test
     * @depends restore_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     * @throws Exception\InvalidNextBillerException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_have_a_rocketgate_biller_on_the_cascade(PurchaseProcess $purchaseProcess): void
    {
        $this->assertSame('rocketgate', $purchaseProcess->cascade()->nextBiller()->name());
    }

    /**
     * @test
     * @depends restore_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     */
    public function was_member_id_generated_should_return_true_after_restore_if_member_id_was_not_sent_into_construct(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertTrue($purchaseProcess->wasMemberIdGenerated());
    }

    /**
     * @test
     * @depends restore_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws Exception\InvalidUserInfoPassword
     * @throws Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\Logger\Exception
     */
    public function generate_user_should_should_create_a_generated_username(
        PurchaseProcess $purchaseProcess
    ): void {

        $purchaseProcess->generateOrUpdateUser();
        $this->assertNotNull($purchaseProcess->userInfo()->username());
    }

    /**
     * @test
     * @depends restore_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess The purchase process object
     * @return void
     * @throws Exception\InvalidUserInfoPassword
     * @throws Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\Logger\Exception
     */
    public function generate_user_should_should_create_a_generated_password(
        PurchaseProcess $purchaseProcess
    ): void {

        $purchaseProcess->generateOrUpdateUser();
        $this->assertNotNull($purchaseProcess->userInfo()->password());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function init_state_according_to_fraud_advice_should_block_process_if_init_captcha_advised(): void
    {
        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isInitCaptchaAdvised')->willReturn(true);

        /** @var MockObject|PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'fraudAdvice',
                    'blockDueToFraudAdvice'
                ]
            )
            ->getMock();
        $purchaseProcess->method('fraudAdvice')->willReturn($fraudAdvice);

        $purchaseProcess->expects($this->once())->method('blockDueToFraudAdvice');

        $purchaseProcess->initStateAccordingToFraudAdvice();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function init_state_according_to_fraud_advice_should_block_process_if_blacklisted_on_init(): void
    {
        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isBlacklistedOnInit')->willReturn(true);

        /** @var MockObject|PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'fraudAdvice',
                    'blockDueToFraudAdvice'
                ]
            )
            ->getMock();
        $purchaseProcess->method('fraudAdvice')->willReturn($fraudAdvice);

        $purchaseProcess->expects($this->once())->method('blockDueToFraudAdvice');

        $purchaseProcess->initStateAccordingToFraudAdvice();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function init_state_according_to_fraud_advice_should_validate_if_no_fraud_advice_triggered(): void
    {
        $fraudAdvice = $this->createMock(FraudAdvice::class);

        /** @var MockObject|PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'fraudAdvice',
                    'validate'
                ]
            )
            ->getMock();
        $purchaseProcess->method('fraudAdvice')->willReturn($fraudAdvice);

        $purchaseProcess->expects($this->once())->method('validate');

        $purchaseProcess->initStateAccordingToFraudAdvice();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function init_state_according_to_fraud_advice_should_throw_invalid_state_transition_exception_if_no_fraud_advice_exits(
    ): void
    {
        $this->expectException(IllegalStateTransitionException::class);

        /** @var MockObject|PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $purchaseProcess->initStateAccordingToFraudAdvice();
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function is_fraud_should_return_true_if_captcha_is_not_validated(PurchaseProcess $purchaseProcess): void
    {

        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isCaptchaValidated')->willReturn(false);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function is_fraud_should_return_true_if_is_blacklisted_on_process(PurchaseProcess $purchaseProcess): void
    {

        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isBlacklistedOnProcess')->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function is_fraud_should_return_true_if_is_blacklisted_on_init(PurchaseProcess $purchaseProcess): void
    {

        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isBlacklistedOnInit')->willReturn(true);

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $this->assertTrue($purchaseProcess->isFraud());
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     * @return void
     */
    public function was_main_item_purchase_successful_method_should_return_a_boolean_response(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertIsBool($purchaseProcess->wasMainItemPurchaseSuccessful());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function post_processing_should_call_cascade(): void
    {
        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchasePending')->willReturn(false);

        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'retrieveMainPurchaseItem',
                    'finishProcessing',
                    'startPending',
                    'validate',
                    'cascade',
                    'isExistingMemberPurchase'
                ]
            )
            ->getMock();

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $purchaseProcess->expects($this->once())->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new RocketgateBiller(),
                    ]
                )
            )
        );

        $purchaseProcess->postProcessing();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function post_processing_should_call_finish_processing_when_main_item_purchase_is_successful(): void
    {
        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchasePending')->willReturn(false);

        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'retrieveMainPurchaseItem',
                    'finishProcessing',
                    'startPending',
                    'validate',
                    'cascade',
                    'isExistingMemberPurchase'
                ]
            )
            ->getMock();

        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new RocketgateBiller(),
                    ]
                )
            )
        );

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $purchaseProcess->expects($this->once())->method('finishProcessing');

        $purchaseProcess->postProcessing();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function post_processing_should_call_start_pending_when_main_item_purchase_is_pending(): void
    {
        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemPurchasePending')->willReturn(true);

        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'retrieveMainPurchaseItem',
                    'finishProcessing',
                    'startPending',
                    'validate',
                    'cascade',
                    'isExistingMemberPurchase'
                ]
            )
            ->getMock();

        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new RocketgateBiller(),
                    ]
                )
            )
        );

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $purchaseProcess->expects($this->once())->method('startPending');

        $purchaseProcess->postProcessing();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function post_processing_should_call_validate_when_main_item_purchase_is_not_successful_or_pending(): void
    {
        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(false);
        $initializedItem->method('wasItemPurchasePending')->willReturn(false);

        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'retrieveMainPurchaseItem',
                    'finishProcessing',
                    'startPending',
                    'validate',
                    'updateProcessStateBasedOnCascadeRemainingBillers',
                    'cascade',
                    'isExistingMemberPurchase'
                ]
            )
            ->getMock();

        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                        new RocketgateBiller(),
                    ]
                )
            )
        );

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $purchaseProcess->expects($this->once())->method('validate');

        $purchaseProcess->postProcessing();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function filter_billers_if_three_ds_advised_method_shouold_call_remove_non_three_ds_billers_on_cascade_if_is_force_three_d_is_advised(
    ): void
    {
        $fraudMock = $this->createMock(FraudAdvice::class);
        $fraudMock->method('isForceThreeD')->willReturn(true);

        $cascadeMock = $this->createMock(Cascade::class);
        $cascadeMock->expects($this->once())->method('removeNonThreeDSBillers');

        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'fraudAdvice',
                    'cascade',
                ]
            )
            ->getMock();
        $purchaseProcess->method('fraudAdvice')->willReturn($fraudMock);
        $purchaseProcess->method('cascade')->willReturn($cascadeMock);

        $purchaseProcess->filterBillersIfThreeDSAdvised();
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function build_item_properties_should_not_throw_type_error_when_wrong_types_than_expected_are_provided_with_rebill(
    )
    {
        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['buildItemProperties'])
            ->getMock();

        $item = [
            'amount'       => true,
            'initialDays'  => true,
            'rebillDays'   => true,
            'rebillAmount' => true,
            'tax'          => [
                'initialAmount'    => [
                    'beforeTaxes' => true,
                    'taxes'       => true,
                    'afterTaxes'  => true,
                ],
                'rebillAmount'     => [
                    'beforeTaxes' => true,
                    'taxes'       => true,
                    'afterTaxes'  => true,
                ],
                'taxApplicationId' => true,
                'taxName'          => true,
                'taxRate'          => true,
                'custom'           => true,
                'taxType'          => true,
            ],
        ];

        $itemProperties = $purchaseProcess->buildItemProperties($item);
        $this->assertIsArray($itemProperties);
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function build_item_properties_should_not_throw_type_error_when_wrong_types_than_expected_are_provided_no_rebill_and_missing_optional_fields(
    )
    {
        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['buildItemProperties'])
            ->getMock();

        $item = [
            'amount'      => true,
            'initialDays' => true,
            'tax'         => [
                'initialAmount' => [
                    'beforeTaxes' => true,
                    'taxes'       => true,
                    'afterTaxes'  => true,
                ],
                'taxRate'       => true,
            ],
        ];

        $itemProperties = $purchaseProcess->buildItemProperties($item);
        $this->assertIsArray($itemProperties);
    }

    /**
     * @test
     * @return void
     * @throws InvalidAmountException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function initialize_item_should_not_throw_type_error_when_wrong_types_than_expected_are_provided(): void
    {
        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(CCPaymentInfo::class),
            new InitializedItemCollection(),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            null
        );

        // For siteId, bundleId and addonId, we have to provide the valid data, otherwise they throw a validation
        // exception and stop the flow.. but before having the valid ids, I've put boolean to provoke the TypeError
        // to make sure the type error is now addressed by the type cast added.
        // Check the pull-request where I left a comment about this.
        $item = [
            'siteId'         => $this->faker->uuid,
            'bundleId'       => $this->faker->uuid,
            'addonId'        => $this->faker->uuid,
            'isTrial'        => 'whatever',
            'subscriptionId' => true,
            'amount'         => true,
            'initialDays'    => true,
            'tax'            => [
                'initialAmount' => [
                    'beforeTaxes' => true,
                    'taxes'       => true,
                    'afterTaxes'  => true,
                ],
                'taxRate'       => true,
            ],
        ];

        $purchaseProcess->initializeItem($item);
        $this->assertNotEmpty($purchaseProcess->initializedItemCollection());
    }

    /**
     * @test
     * @return void
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception\InvalidCurrency
     * @throws Exception\InvalidUserInfoEmail
     * @throws Exception\InvalidZipCodeException
     * @throws Exception\ItemCouldNotBeRestoredException
     * @throws Exception\UnsupportedPaymentTypeException
     * @throws Exception\ValidationException
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function restore_should_keep_cascade_in_the_same_form()
    {
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $cascade = [
            'billers'               => [
                'rocketgate',
                'netbilling',
            ],
            'currentBiller'         => 'rocketgate',
            'currentBillerSubmit'   => 1,
            'currentBillerPosition' => 0,
            'removedBillersFor3DS'  => []
        ];

        $sessionPayload['cascade'] = $cascade;

        $sessionRestored = PurchaseProcess::restore($sessionPayload);

        $this->assertSame($cascade, $sessionRestored->cascade()->toArray());
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     */
    public function it_should_return_total_amount_from_initial_amount(): void
    {
        $mainAmount  = $this->faker->randomFloat();
        $crossAmount = $this->faker->randomFloat();
        $totalAmount = $mainAmount + $crossAmount;

        $purchaseProcess = $this->returnPurchaseProcess();

        $mainItem = [
            'siteId'         => $this->faker->uuid,
            'bundleId'       => $this->faker->uuid,
            'addonId'        => $this->faker->uuid,
            'isTrial'        => 'true',
            'subscriptionId' => $this->faker->uuid,
            'amount'         => $mainAmount,
            'initialDays'    => 1
        ];

        $xSaleItem = [
            'siteId'         => $this->faker->uuid,
            'bundleId'       => $this->faker->uuid,
            'addonId'        => $this->faker->uuid,
            'isTrial'        => 'true',
            'subscriptionId' => $this->faker->uuid,
            'amount'         => $crossAmount,
            'initialDays'    => 1
        ];

        $purchaseProcess->initializeItem($mainItem);
        $purchaseProcess->initializeItem($xSaleItem, true);


        $totalAmountFromMethod = $purchaseProcess->totalAmount();

        $this->assertEquals($totalAmount, $totalAmountFromMethod->value());
    }

    /**
     * @return PurchaseProcess
     * @throws \ProBillerNG\Logger\Exception
     */
    private function returnPurchaseProcess(): PurchaseProcess
    {
        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(CCPaymentInfo::class),
            new InitializedItemCollection(),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            null
        );

        return $purchaseProcess;
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_return_true_when_transaction_is_found(PurchaseProcess $purchaseProcess): void
    {
        $transactionId = TransactionId::createFromString($this->faker->uuid);

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add(
            Transaction::create(
                $transactionId,
                Transaction::STATUS_PENDING,
                EpochBiller::BILLER_NAME
            )
        );

        $doesTransactionExist = $purchaseProcess->checkIfTransactionIdExist((string) $transactionId);

        $this->assertTrue($doesTransactionExist);
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_transaction_is_not_found(PurchaseProcess $purchaseProcess): void
    {
        $doesTransactionExist = $purchaseProcess->checkIfTransactionIdExist($this->faker->uuid);

        $this->assertFalse($doesTransactionExist);
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_change_state_of_a_transaction_object_when_transaction_is_found(
        PurchaseProcess $purchaseProcess
    ): void {
        $transactionId = TransactionId::createFromString($this->faker->uuid);

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add(
            Transaction::create(
                $transactionId,
                Transaction::STATUS_PENDING,
                EpochBiller::BILLER_NAME
            )
        );

        $purchaseProcess->updateTransactionStateFor(
            (string) $transactionId,
            Transaction::STATUS_APPROVED
        );

        $this->assertSame(
            Transaction::STATUS_APPROVED,
            $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionState()
        );
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return PurchaseProcess
     * @throws \Exception
     */
    public function it_should_update_the_purchase_process_with_received_updates_from_third_party(
        PurchaseProcess $purchaseProcess
    ): PurchaseProcess {
        $purchaseProcess->returnFromThirdPartyUpdates(
            (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
            Transaction::STATUS_APPROVED,
            CCPaymentInfo::PAYMENT_TYPE,
            'visa'
        );

        $this->assertSame($purchaseProcess->retrieveMainPurchaseItem()->lastTransactionState(),
            Transaction::STATUS_APPROVED);

        return $purchaseProcess;
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_have_the_correct_payment_type(PurchaseProcess $purchaseProcess): void
    {
        $this->assertSame($purchaseProcess->paymentInfo()->paymentType(), CCPaymentInfo::PAYMENT_TYPE);
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_have_the_correct_payment_method(PurchaseProcess $purchaseProcess): void
    {
        $this->assertSame($purchaseProcess->paymentMethod(), 'visa');
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_update_the_purchase_process_with_received_updates_from_third_party_without_payment_type_if_empty(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->returnFromThirdPartyUpdates(
            (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
            Transaction::STATUS_APPROVED,
            '',
            'Visa'
        );

        $this->assertNotSame($purchaseProcess->paymentInfo()->paymentType(), '');
    }

    /**
     * @test
     * @depends create_should_return_a_purchase_process_object
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws \Exception
     */
    public function it_should_update_the_purchase_process_with_received_updates_from_third_party_without_payment_method_if_empty(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->returnFromThirdPartyUpdates(
            (string) $purchaseProcess->retrieveMainPurchaseItem()->lastTransactionId(),
            Transaction::STATUS_APPROVED,
            '',
            ''
        );

        $this->assertNotSame($purchaseProcess->paymentMethod(), '');
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function post_processing_should_not_call_validate_state_when_main_item_purchase_is_nfs_purchase(): void
    {

        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $initializedItem->method('wasItemPurchasePending')->willReturn(false);

        $transactionId = TransactionId::createFromString($this->faker->uuid);
        $transaction = Transaction::create(
            $transactionId,
            Transaction::STATUS_DECLINED,
            RocketgateBiller::BILLER_NAME,
            true,
            null,
            null,
            null,
            true
        );

        $initializedItem->transactionCollection()->add($transaction);

        /** @var PurchaseProcess|MockObject $purchaseProcess */
        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'retrieveMainPurchaseItem',
                    'finishProcessing',
                    'startPending',
                    'validate',
                    'updateProcessStateBasedOnCascadeRemainingBillers',
                    'cascade',
                    'isExistingMemberPurchase'
                ]
            )
            ->getMock();

        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [
                    ]
                )
            )
        );

        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);

        $purchaseProcess->expects($this->never())->method('validate');

        $purchaseProcess->postProcessing();
    }
}
