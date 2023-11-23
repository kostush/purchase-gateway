<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\VoidTransactions;

use Exception;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\Services\VoidTransactions\VoidTransactionsCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\Publisher;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ReflectionClass;
use Tests\IntegrationTestCase;

class VoidTransactionsCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @var array
     */
    private $eventBody;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->eventBody = [
            'aggregate_id'             => $this->faker->uuid,
            'occurred_on'              => '2021-03-10 14:26:06.955204',
            'version'                  => 13,
            'purchase_id'              => $this->faker->uuid,
            'transaction_collection'   => [
                [
                    'state'               => Transaction::STATUS_APPROVED,
                    'transactionId'       => $this->faker->uuid,
                    'newCCUsed'           => true,
                    'billerName'          => RocketgateBiller::BILLER_NAME,
                    'acs'                 => '',
                    'pareq'               => '',
                    'redirectUrl'         => null,
                    'isNsf'               => false,
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
            'status'                   => 'success',
            'member_id'                => $this->faker->uuid,
            'subscription_id'          => $this->faker->uuid,
            'entry_site_id'            => null,
            'member_info'              => [
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
                'country'     => $this->faker->countryCode,
                'email'       => $this->faker->email,
                'firstName'   => $this->faker->firstName,
                'ipAddress'   => $this->faker->ipv4,
                'lastName'    => $this->faker->lastName,
                'password'    => $this->faker->password,
                'phoneNumber' => $this->faker->phoneNumber,
                'state'       => null,
                'username'    => $this->faker->userName,
                'zipCode'     => $this->faker->postcode,
            ],
            'cross_sale_purchase_data' => [],
            'payment'                  => [
                'ccNumber'        => '*******',
                'cvv'             => '*******',
                'expirationMonth' => '05',
                'expirationYear'  => '2023'
            ],
            'item_id'                  => $this->faker->uuid,
            'bundle_id'                => $this->faker->uuid,
            'add_on_id'                => $this->faker->uuid,
            'subscription_username'    => $this->faker->userName,
            'subscription_password'    => $this->faker->password,
            'rebill_frequency'         => 30,
            'initial_days'             => 2,
            'atlas_code'               => 'NDU1MDk1OjQ4OjE0Nw',
            'atlas_data'               => 'atlas data example',
            'ip_address'               => $this->faker->ipv4,
            'is_trial'                 => true,
            'amount'                   => 50,
            'rebill_amount'            => 50,
            'amounts'                  => null,
            'is_existing_member'       => false,
            'three_drequired'          => false,
            'is_third_party'           => false,
            'is_nsf'                   => false,
            'payment_method'           => $this->faker->creditCardType,
            'traffic_source'           => 'ALL',
            'threed_version'           => null,
            'threed_frictionless'      => false,
            'skip_void_transaction'    => false
        ];

        $_ENV['ENABLE_VOID_TRANSACTION_FEATURE'] = true;
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $_ENV['ENABLE_VOID_TRANSACTION_FEATURE'] = false;
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_publish_one_transaction_to_void_if_transaction_is_made_with_new_cc(): void
    {
        $retrievedTransaction = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $retrievedTransaction->method('billerFields')->willReturn(
            RocketgateBillerFields::create(
                'merchantId',
                'merchantPassword',
                'billerSiteId',
                'sharedSecret',
                true
            )
        );
        $retrievedTransaction->method('transactionInformation')->willReturn(
            $this->createMock(NewCCTransactionInformation::class)
        );

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($retrievedTransaction);

        $publisher = $this->createMock(Publisher::class);
        $publisher->expects($this->once())->method('publishTransactionToBeVoided');

        $handler = new VoidTransactionsCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $transactionService,
            $this->createMock(PaymentTemplateTranslatingService::class),
            $publisher
        );

        $reflection = new ReflectionClass(VoidTransactionsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $event = $this->createMock(ItemToWorkOn::class);
        $event->method('body')->willReturn(json_encode($this->eventBody));

        $method->invoke($handler, $event);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_publish_one_transaction_to_void_if_transaction_is_made_with_payment_template(): void
    {
        $retrievedTransaction = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $retrievedTransaction->method('billerFields')->willReturn(
            RocketgateBillerFields::create(
                'merchantId',
                'merchantPassword',
                'billerSiteId',
                'sharedSecret',
                true
            )
        );

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($retrievedTransaction);

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn((string) $this->faker->numberBetween('400000', '499999'));
        $paymentTemplate->method('lastFour')->willReturn((string) $this->faker->numberBetween('0000', '9999'));
        $paymentTemplate->method('expirationYear')->willReturn((string) $this->faker->numberBetween('2030', '2099'));
        $paymentTemplate->method('expirationMonth')->willReturn((string) $this->faker->numberBetween('1', '12'));
        $paymentTemplate->expects($this->once())->method('firstSix');

        $paymentTemplateService = $this->createMock(PaymentTemplateTranslatingService::class);
        $paymentTemplateService->method('retrievePaymentTemplate')->willReturn($paymentTemplate);

        $publisher = $this->createMock(Publisher::class);
        $publisher->expects($this->once())->method('publishTransactionToBeVoided');

        $handler = new VoidTransactionsCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $transactionService,
            $paymentTemplateService,
            $publisher
        );

        $reflection = new ReflectionClass(VoidTransactionsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $eventBodyWithPaymentTemplate = $this->eventBody;

        $eventBodyWithPaymentTemplate['payment'] = [
            'cardHash'          => '*******',
            'paymentTemplateId' => $this->faker->uuid
        ];

        $event = $this->createMock(ItemToWorkOn::class);
        $event->method('body')->willReturn(json_encode($eventBodyWithPaymentTemplate));

        $method->invoke($handler, $event);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_publish_two_transactions_to_void(): void
    {
        $retrievedTransaction = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $retrievedTransaction->method('billerFields')->willReturn(
            RocketgateBillerFields::create(
                'merchantId',
                'merchantPassword',
                'billerSiteId',
                'sharedSecret',
                true
            )
        );
        $retrievedTransaction->method('transactionInformation')->willReturn(
            $this->createMock(NewCCTransactionInformation::class)
        );

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($retrievedTransaction);

        $publisher = $this->createMock(Publisher::class);
        $publisher->expects($this->exactly(2))->method('publishTransactionToBeVoided');

        $handler = new VoidTransactionsCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $transactionService,
            $this->createMock(PaymentTemplateTranslatingService::class),
            $publisher
        );

        $reflection = new ReflectionClass(VoidTransactionsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $eventBody = $this->eventBody;

        $eventBody['cross_sale_purchase_data'][]['transactionCollection'][] = [
            'state'               => Transaction::STATUS_APPROVED,
            'transactionId'       => $this->faker->uuid,
            'newCCUsed'           => true,
            'billerName'          => RocketgateBiller::BILLER_NAME,
            'acs'                 => '',
            'pareq'               => '',
            'redirectUrl'         => null,
            'isNsf'               => false,
            'deviceCollectionUrl' => null,
            'deviceCollectionJwt' => null,
            'deviceFingerprintId' => null,
            'threeDStepUpUrl'     => null,
            'threeDStepUpJwt'     => null,
            'md'                  => null,
            'threeDFrictionless'  => false,
            'threeDVersion'       => null
        ];

        $event = $this->createMock(ItemToWorkOn::class);
        $event->method('body')->willReturn(json_encode($eventBody));

        $method->invoke($handler, $event);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_not_publish_any_events_if_last_transaction_is_not_approved(): void
    {
        $publisher = $this->createMock(Publisher::class);
        $publisher->expects($this->never())->method('publishTransactionToBeVoided');

        $handler = new VoidTransactionsCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $this->createMock(TransactionService::class),
            $this->createMock(PaymentTemplateTranslatingService::class),
            $publisher
        );

        $reflection = new ReflectionClass(VoidTransactionsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $eventBody = $this->eventBody;

        $eventBody['transaction_collection'][0]['state'] = Transaction::STATUS_DECLINED;

        $event = $this->createMock(ItemToWorkOn::class);
        $event->method('body')->willReturn(json_encode($eventBody));

        $method->invoke($handler, $event);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_not_publish_any_events_if_skip_void_is_true(): void
    {
        $publisher = $this->createMock(Publisher::class);
        $publisher->expects($this->never())->method('publishTransactionToBeVoided');

        $handler = new VoidTransactionsCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $this->createMock(TransactionService::class),
            $this->createMock(PaymentTemplateTranslatingService::class),
            $publisher
        );

        $reflection = new ReflectionClass(VoidTransactionsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $eventBody = $this->eventBody;

        $eventBody['skip_void_transaction'] = true;

        $event = $this->createMock(ItemToWorkOn::class);
        $event->method('body')->willReturn(json_encode($eventBody));

        $method->invoke($handler, $event);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_not_publish_any_events_if_payment_is_not_made_with_card(): void
    {
        $publisher = $this->createMock(Publisher::class);
        $publisher->expects($this->never())->method('publishTransactionToBeVoided');

        $handler = new VoidTransactionsCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $this->createMock(TransactionService::class),
            $this->createMock(PaymentTemplateTranslatingService::class),
            $publisher
        );

        $reflection = new ReflectionClass(VoidTransactionsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $eventBody = $this->eventBody;

        unset($eventBody['payment']['ccNumber']);

        $event = $this->createMock(ItemToWorkOn::class);
        $event->method('body')->willReturn(json_encode($eventBody));

        $method->invoke($handler, $event);
    }
}
