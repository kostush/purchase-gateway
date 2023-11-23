<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use Exception;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocity;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Tests\UnitTestCase;

/**
 * Class FraudPurchaseVelocityTest
 * @package Tests\Unit\PurchaseGateway\Application\BI
 * @group   event-ingestion
 */
class FraudPurchaseVelocityTest extends UnitTestCase
{
    /**
     * @var Generator
     */
    protected $faker;
    /**
     * @var Site
     */
    protected $randomSite;

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();

        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(
            Service::create('Service name', true)
        );
        $publicKeyCollection = new PublicKeyCollection();

        $publicKeyCollection->add(
            PublicKey::create(
                KeyId::createFromString('3dcc4a19-e2a8-4622-8e03-52247bbd302d'),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2019-11-15 16:11:41.0000')
            )
        );

        $this->randomSite = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $serviceCollection,
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $publicKeyCollection,
            'Business group descriptor',
            false,
            false,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );
    }

    /**
     * @test
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function event_should_be_created_successfully_from_purchase_processed(): void
    {
        $ip           = $this->createMock(Ip::class);
        $paymentoInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $event        = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $this->createPurchaseProcessed(),
            $ip,
            $this->randomSite,
            $paymentoInfo,
            null
        );
        $this->assertInstanceOf(FraudPurchaseVelocity::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function event_created_should_have_correct_card_information(): void
    {
        $data = [
            'first6' => $this->faker->numerify('######'),
            'last4'  => $this->faker->numerify('####')
        ];

        $ip          = $this->createMock(Ip::class);
        $paymentInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $this->createPurchaseProcessed($data),
            $ip,
            $this->randomSite,
            $paymentInfo,
            null
        );
        $this->assertEquals($data['first6'] . $data['last4'], $event->jsonSerialize()['payment']['card']);
    }

    /**
     * @test
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function event_created_should_have_correct_domain_information(): void
    {
        $data = ['email' => 'test.mindgeek.com'];

        $ip          = $this->createMock(Ip::class);
        $paymentInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $this->createPurchaseProcessed($data),
            $ip,
            $this->randomSite,
            $paymentInfo,
            null
        );
        $this->assertEquals($data['email'], $event->jsonSerialize()['memberInfo']['domain']);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     */
    public function extract_domain_should_work(): void
    {
        $event = new FraudPurchaseVelocity('', '', '', [], [], 0.00, 0.00, 0.00, 0, '');

        $fakeEmail = 'test@test.mindgeek.com';
        $method    = self::getMethod('extractDomainFromEmail');
        $result    = $method->invokeArgs($event, [$fakeEmail]);
        $this->assertEquals('test.mindgeek.com', $result);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     */
    public function initial_amount_from_approved_should_count_approved_item(): void
    {
        $event = new FraudPurchaseVelocity('', '', '', [], [], 0.00, 0.00, 0.00, 0, '');

        $randomFloat = $this->faker->randomFloat(2);
        $item        = [
            'status'        => Transaction::STATUS_APPROVED,
            'initialAmount' => $randomFloat
        ];
        $method      = self::getMethod('initialAmountFromApproved');
        $result      = $method->invokeArgs($event, [$item]);
        $this->assertEquals($randomFloat, $result);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     */
    public function initial_amount_from_approved_should_not_count_not_approved_item(): void
    {
        $event = new FraudPurchaseVelocity('', '', '', [], [], 0.00, 0.00, 0.00, 0, '');

        $item   = [
            'status'        => 'declined',
            'initialAmount' => $this->faker->randomFloat(2)
        ];
        $method = self::getMethod('initialAmountFromApproved');
        $result = $method->invokeArgs($event, [$item]);
        $this->assertEquals(0.00, $result);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     */
    public function find_cross_sells_approved_amount_should_sum_only_approved_items(): void
    {
        $event = new FraudPurchaseVelocity('', '', '', [], [], 0.00, 0.00, 0.00, 0, '');

        $amount = $this->faker->randomFloat(2);
        $item   = [
            [
                'status'        => Transaction::STATUS_APPROVED,
                'initialAmount' => $amount
            ],
            [
                'status'        => 'declined',
                'initialAmount' => $this->faker->randomFloat(2)
            ],
            [
                'status'        => Transaction::STATUS_APPROVED,
                'initialAmount' => $amount
            ]
        ];
        $method = self::getMethod('sumApprovedCrossSellsAmount');
        $result = $method->invokeArgs($event, [$item]);
        $this->assertEquals($amount * 2, $result);
    }

    /**
     * @test
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_contain_bypass_payment_template_validation_with_true_value(): void
    {
        $ip          = $this->createMock(Ip::class);
        $paymentInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $this->createPurchaseProcessed(),
            $ip,
            $this->randomSite,
            $paymentInfo,
            true
        );

        $this->assertArrayHasKey('bypassPaymentTemplateValidation', $event->jsonSerialize());
        $this->assertTrue($event->jsonSerialize()['bypassPaymentTemplateValidation']);
    }

    /**
     * @test
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_contain_bypass_payment_template_validation_with_false_value(): void
    {
        $ip          = $this->createMock(Ip::class);
        $paymentInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $this->createPurchaseProcessed(),
            $ip,
            $this->randomSite,
            $paymentInfo,
            false
        );

        $this->assertArrayHasKey('bypassPaymentTemplateValidation', $event->jsonSerialize());
        $this->assertFalse($event->jsonSerialize()['bypassPaymentTemplateValidation']);
    }

    /**
     * @test
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_not_contain_bypass_payment_template_validation_key(): void
    {
        $ip          = $this->createMock(Ip::class);
        $paymentInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $this->createPurchaseProcessed(),
            $ip,
            $this->randomSite,
            $paymentInfo,
            null
        );

        $this->assertArrayNotHasKey('bypassPaymentTemplateValidation', $event->jsonSerialize());
    }

    /**
     * @param string $name Name.
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getMethod($name)
    {
        $class  = new ReflectionClass(FraudPurchaseVelocity::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @param array $data Data.
     * @return MockObject|PurchaseProcessed
     */
    private function createPurchaseProcessed(array $data = [])
    {
        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed->method('toArray')->willReturn(
            [
                'memberInfo'            => [
                    'email'       => $data['email'] ?? $this->faker->email,
                    'username'    => $this->faker->userName,
                    'firstName'   => $this->faker->firstName,
                    'lastName'    => $this->faker->lastName,
                    'countryCode' => $this->faker->countryCode,
                    'zipCode'     => $this->faker->postcode,
                    'address'     => $this->faker->address,
                    'city'        => $this->faker->city,
                ],
                'payment'               => [
                    'first6' => $data['first6'] ?? $this->faker->numerify('######'),
                    'last4'  => $data['last4'] ?? $this->faker->numerify('####'),
                ],
                'selectedCrossSells'    => [
                    [
                        'status'        => Transaction::STATUS_APPROVED,
                        'initialAmount' => $this->faker->randomFloat(2)
                    ],
                    [
                        'status'        => 'declined',
                        'initialAmount' => $this->faker->randomFloat(2)
                    ],
                    [
                        'status'        => Transaction::STATUS_APPROVED,
                        'initialAmount' => $this->faker->randomFloat(2)
                    ]
                ],
                'status'                => Transaction::STATUS_APPROVED,
                'initialAmount'         => $this->faker->randomFloat(2),
                'attemptedTransactions' => [
                    'submitAttempt' => $this->faker->numberBetween(1, 10),
                    'billerName'    => $this->faker->word
                ],
            ]
        );

        return $purchaseProcessed;
    }
}
