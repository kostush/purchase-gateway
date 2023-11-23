<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Redirected;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDLookupPerformed;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\ThreeDAuthenticated;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotProcessPurchaseWithoutCaptchaValidationException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use Tests\IntegrationTestCase;

class PurchaseProcessTest extends IntegrationTestCase
{
    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var FraudAdvice
     */
    private $fraudAdvice;

    /**
     * @var Transaction
     */
    private $transaction;

    private const UUID            = '707ab722-b397-11e9-a2a3-2a2ae2dbcce4';
    private const SUBSCRIPTION_ID = 'fefd76a4-ba55-4b04-b1ac-38dc2159610e';

    /**
     * Set up
     *
     * @return void
     *
     * @throws Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function setUp(): void
    {
        parent::setUp();

        $initializedItemCollection = new InitializedItemCollection();

        $item = InitializedItem::create(
            SiteId::create(),
            BundleId::create(),
            AddonId::create(),
            $this->createMock(ChargeInformation::class),
            null,
            false,
            false,
            self::SUBSCRIPTION_ID
        );

        $initializedItemCollection->offsetSet(self::UUID, $item);

        $this->purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(CCPaymentInfo::class),
            $initializedItemCollection,
            $this->faker->uuid,
            $this->faker->uuid,
            CurrencyCode::create('USD'),
            null,
            null,
            'ALL'
        );

        $this->transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_APPROVED,
            RocketgateBiller::BILLER_NAME,
            null
        );

        $this->fraudAdvice = FraudAdvice::create(
            Ip::create($this->faker->ipv4),
            Email::create($this->faker->email),
            Zip::create((string) $this->faker->numberBetween(10000, 99999)),
            Bin::createFromCCNumber($this->faker->creditCardNumber)
        );

        $this->purchaseProcess->setFraudAdvice($this->fraudAdvice);

        $billerCollection = new BillerCollection();
        $billerCollection->add(new RocketgateBiller());
        $billerCollection->add(new NetbillingBiller());

        $this->purchaseProcess->setCascade(Cascade::create($billerCollection));
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws Exception
     */
    public function validate_init_captcha_should_change_the_state_of_the_purchase_process_object_to_valid(): void
    {
        $this->purchaseProcess->blockDueToFraudAdvice();
        $this->purchaseProcess->validateInitCaptcha();
        $this->assertInstanceOf(Valid::class, $this->purchaseProcess->state());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function set_init_state_on_purchase_process_according_to_fraud_advice_should_update_state(): void
    {
        $this->purchaseProcess->initStateAccordingToFraudAdvice();
        $this->assertInstanceOf(Valid::class, $this->purchaseProcess->state());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function validate_on_purchase_process_according_to_fraud_advice_should_update_state_to_valid(): void
    {
        $this->purchaseProcess->validate();
        $this->assertInstanceOf(Valid::class, $this->purchaseProcess->state());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     */
    public function validate_process_captcha_should_throw_exception_if_init_captcha_is_not_validated(): void
    {
        $this->expectException(CannotValidateProcessCaptchaWithoutInitCaptchaException::class);

        $this->fraudAdvice->markInitCaptchaAdvised();
        $this->purchaseProcess->validateProcessCaptcha();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     */
    public function increment_gateway_submit_number_if_valid_method_should_return_the_exact_int_value(): void
    {
        $this->purchaseProcess->blockDueToFraudAdvice();
        $this->purchaseProcess->validateProcessCaptcha();

        $expected = $this->purchaseProcess->gatewaySubmitNumber() + 1;
        $this->purchaseProcess->incrementGatewaySubmitNumberIfValid();

        $this->assertSame($expected, $this->purchaseProcess->gatewaySubmitNumber());
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws Exception
     */
    public function validate_process_captcha_should_change_the_state_of_the_purchase_process_object_to_valid(): PurchaseProcess
    {
        $this->purchaseProcess->blockDueToFraudAdvice();
        $this->purchaseProcess->validateProcessCaptcha();
        $this->assertInstanceOf(Valid::class, $this->purchaseProcess->state());
        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends validate_process_captcha_should_change_the_state_of_the_purchase_process_object_to_valid
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function is_valid_should_return_true_when_captcha_is_validated_on_purchase_process_object($object): void
    {
        $this->assertTrue($object->isValid());
    }

    /**
     * @test
     * @depends validate_process_captcha_should_change_the_state_of_the_purchase_process_object_to_valid
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function start_processing_method_from_purchase_process_object_should_change_state_to_processing_state(
        $object
    ): void {
        $object->startProcessing();
        $this->assertInstanceOf(Processing::class, $object->state());
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process_should_change_the_state_of_the_purchase_process_object_to_processing(): PurchaseProcess
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->process();

        $this->assertInstanceOf(Processing::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends process_should_change_the_state_of_the_purchase_process_object_to_processing
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \Exception
     */
    public function authenticate_method_from_purchase_process_object_should_throw_exception(PurchaseProcess $object): void
    {
        $this->expectException(IllegalStateTransitionException::class);
        $object->authenticateThreeD();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \Exception
     */
    public function process_should_change_the_state_of_the_purchase_process_object_to_pending_if_biller_is_third_party(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [new EpochBiller()]
            )
        );
        $this->purchaseProcess->setCascade($cascade);

        $this->purchaseProcess->validate();
        $this->purchaseProcess->setStateIfThirdParty();

        $this->assertInstanceOf(Pending::class, $this->purchaseProcess->state());
    }

    /**
     * @test
     * @throws IllegalStateTransitionException
     * @throws \Exception
     */
    public function process_should_not_change_the_state_of_the_purchase_process_object_if_biller_is_third_party(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [new QyssoBiller()]
            )
        );
        $this->purchaseProcess->setCascade($cascade);

        $this->purchaseProcess->validate();
        $this->purchaseProcess->setStateIfThirdParty();

        self::assertInstanceOf(Valid::class, $this->purchaseProcess->state());
    }

    /**
     * @test
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function process_should_validate_if_current_biller_is_of_available_payment_method(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [new QyssoBiller()]
            )
        );
        $this->purchaseProcess->setCascade($cascade);

        self::assertTrue($this->purchaseProcess->isCurrentBillerAvailablePaymentsMethods());
    }

    /**
     * @test
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function process_should_validate_if_current_biller_is_not_of_available_payment_method(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [new EpochBiller()]
            )
        );
        $this->purchaseProcess->setCascade($cascade);

        self::assertFalse($this->purchaseProcess->isCurrentBillerAvailablePaymentsMethods());
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process_should_change_the_state_of_the_purchase_process_object_to_pending(): PurchaseProcess
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->process();
        $this->purchaseProcess->startPending();

        $this->assertInstanceOf(Pending::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process_should_change_the_state_of_the_purchase_process_object_from_valid_to_pending(): PurchaseProcess
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->startPending();

        $this->assertInstanceOf(Pending::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends process_should_change_the_state_of_the_purchase_process_object_to_pending
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function was_three_d_started_should_throw_exception_if_purchase_process_state_is_pending(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->expectException(IllegalStateTransitionException::class);

        $purchaseProcess->wasThreeDStarted();
    }

    /**
     * @test
     * @depends process_should_change_the_state_of_the_purchase_process_object_to_pending
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function is_pending_method_should_return_true_when_the_purchase_process_state_is_pending(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertTrue($purchaseProcess->isPending());
    }


    /**
     * @test
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return void
     */
    public function validate_and_redirect_should_change_state_to_redirected(): void
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->redirect();

        $this->assertInstanceOf(Redirected::class, $this->purchaseProcess->state());
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process_should_change_the_state_of_the_purchase_process_object_to_authenticate_three_d(): PurchaseProcess
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->process();
        $this->purchaseProcess->startPending();
        $this->purchaseProcess->authenticateThreeD();

        $this->assertInstanceOf(ThreeDAuthenticated::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends process_should_change_the_state_of_the_purchase_process_object_to_authenticate_three_d
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function is_three_d_authenticated_method_should_return_true_when_the_purchase_process_state_is_three_d_authenticated(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertTrue($purchaseProcess->isThreeDAuthenticated());
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process_should_change_the_state_of_the_purchase_process_object_to_lookup_three_d(): PurchaseProcess
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->process();
        $this->purchaseProcess->startPending();
        $this->purchaseProcess->performThreeDLookup();

        $this->assertInstanceOf(ThreeDLookupPerformed::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends process_should_change_the_state_of_the_purchase_process_object_to_lookup_three_d
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     */
    public function is_three_d_lookup_performed_method_should_return_true_when_the_purchase_process_state_is_three_d_lookup(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->assertTrue($purchaseProcess->isThreeDLookupPerformed());
    }

    /**
     * @test
     * @depends process_should_change_the_state_of_the_purchase_process_object_to_pending
     * @param PurchaseProcess $purchaseProcess Purchase Process
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     */
    public function was_three_d_started_should_throw_exception_if_purchase_process_state_is_three_d_authenticated(
        PurchaseProcess $purchaseProcess
    ): void {
        $this->expectException(IllegalStateTransitionException::class);

        $purchaseProcess->wasThreeDStarted();
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked(): PurchaseProcess
    {
        $this->purchaseProcess->blockDueToFraudAdvice();

        $this->assertInstanceOf(BlockedDueToFraudAdvice::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function it_should_return_a_payment_info_object(
        $object
    ): void {
        $this->assertInstanceOf(CCPaymentInfo::class, $object->paymentInfo());
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function it_should_return_a_user_info_object(
        $object
    ): void {
        $this->assertInstanceOf(UserInfo::class, $object->userInfo());
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function public_key_index_method_should_return_a_int_value(
        $object
    ): void {
        $this->assertIsInt($object->publicKeyIndex());
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function gateway_submit_number_method_should_return_a_int_value(
        $object
    ): void {
        $this->assertIsInt($object->gatewaySubmitNumber());
    }

    /**
     * @test
     * @depends validate_process_captcha_should_change_the_state_of_the_purchase_process_object_to_valid
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function increment_gateway_submit_number_method_should_return_the_exact_int_value(
        $object
    ): void {
        $expected = $object->gatewaySubmitNumber() + 1;
        $object->incrementGatewaySubmitNumber();

        $this->assertSame($expected, $object->gatewaySubmitNumber());
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function retrieve_main_purchase_item_method_should_return_a_initialized_item_object(
        $object
    ): void {
        $this->assertInstanceOf(InitializedItem::class, $object->retrieveMainPurchaseItem());
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function main_purchase_subscription_id_method_should_return_the_exact_value(
        $object
    ): void {
        $this->assertSame(self::SUBSCRIPTION_ID, $object->mainPurchaseSubscriptionId());
    }

    /**
     * @test
     * @depends blocked_due_to_fraud_advice_should_change_the_state_of_the_purchase_process_object_to_blocked
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function retrieve_initialized_cross_sales_method_should_return_an_array(
        $object
    ): void {
        $this->assertIsArray($object->retrieveInitializedCrossSales());
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function it_should_change_the_purchase_process_state_to_valid_when_no_transaction_attempted(): PurchaseProcess
    {
        $this->purchaseProcess->postProcessing();

        $this->assertInstanceOf(Valid::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @return PurchaseProcess
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \Exception
     */
    public function it_should_change_the_purchase_process_state_to_cascade_billers_exhausted_when_no_more_billers_are_available_on_the_cascade(): PurchaseProcess
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller()
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS,
            0
        );
        $this->purchaseProcess->setCascade($cascade);
        $this->purchaseProcess->validate();
        $this->purchaseProcess->startProcessing();
        $this->purchaseProcess->postProcessing();

        $this->assertInstanceOf(Processed::class, $this->purchaseProcess->state());
        //$this->assertInstanceOf(CascadeBillersExhausted::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends it_should_change_the_purchase_process_state_to_valid_when_no_transaction_attempted
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \Exception
     * @throws Exception
     */
    public function it_should_change_the_purchase_process_state_to_processed_when_no_attempts_left(
        PurchaseProcess $purchaseProcess
    ): void {

        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller()
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS - 1,
            0
        );
        $cascade->nextBiller();

        $purchaseProcess->setCascade($cascade);
        $purchaseProcess->process();
        $purchaseProcess->incrementGatewaySubmitNumber();
        $purchaseProcess->postProcessing();

        $this->assertInstanceOf(Processed::class, $purchaseProcess->state());
    }

    /**
     * @test
     * @depends it_should_change_the_purchase_process_state_to_valid_when_no_transaction_attempted
     * @param PurchaseProcess $purchaseProcess Purchase process
     * @return void
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \Exception
     * @throws Exception
     */
    public function it_should_change_the_purchase_process_state_to_processed_when_transaction_was_successful(
        PurchaseProcess $purchaseProcess
    ): void {
        $purchaseProcess->addTransactionToItem(
            $this->transaction,
            self::UUID
        );

        $purchaseProcess->postProcessing();

        $this->assertInstanceOf(Processed::class, $purchaseProcess->state());
    }

    /**
     * @test
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @return PurchaseProcess
     */
    public function finish_processing_should_change_the_state_of_the_purchase_process_object_to_processed(): PurchaseProcess
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->process();
        $this->purchaseProcess->finishProcessing();

        $this->assertInstanceOf(Processed::class, $this->purchaseProcess->state());

        return $this->purchaseProcess;
    }

    /**
     * @test
     * @depends finish_processing_should_change_the_state_of_the_purchase_process_object_to_processed
     * @param PurchaseProcess $object PurchaseProcess
     * @return void
     */
    public function is_processed_method_should_return_true_when_the_purchase_process_state_is_processed($object): void
    {
        $this->assertTrue($object->isProcessed());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws CannotProcessPurchaseWithoutCaptchaValidationException
     */
    public function process_should_throw_exception_if_process_captcha_is_not_validated(): void
    {
        $this->expectException(IllegalStateTransitionException::class);
        $this->purchaseProcess->process();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function add_transactions_to_items_should_add_transaction_objects_to_initialized_items_collection(): void
    {
        $this->purchaseProcess->addTransactionToItem(
            $this->transaction,
            self::UUID
        );

        $initializedItem = $this->purchaseProcess->initializedItemCollection()->offsetGet(self::UUID);
        $this->assertNotEmpty($initializedItem->transactionCollection());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function it_should_throw_illegal_state_transition_exception_if_state_is_pending(): void
    {
        $this->expectException(IllegalStateTransitionException::class);

        $this->purchaseProcess->validate();
        $this->purchaseProcess->startPending();

        $this->purchaseProcess->wasStartedWithThirdPartyBiller();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function it_should_throw_illegal_state_transition_exception_if_state_is_redirected(): void
    {
        $this->expectException(IllegalStateTransitionException::class);

        $this->purchaseProcess->validate();
        $this->purchaseProcess->redirect();

        $this->purchaseProcess->wasStartedWithThirdPartyBiller();
    }

    /**
     * @test
     *
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     */
    public function it_should_complete_process_from_pending_state_successfully(): void
    {
        $this->purchaseProcess->validate();
        $this->purchaseProcess->startPending();
        $this->purchaseProcess->finishProcessing();

        $this->assertInstanceOf(Processed::class, $this->purchaseProcess->state());
    }
}
