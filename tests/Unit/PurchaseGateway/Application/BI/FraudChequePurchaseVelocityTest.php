<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use Exception;
use Faker\Factory;
use Faker\Generator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Application\BI\FraudChequePurchaseVelocity;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocity;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use Tests\UnitTestCase;

/**
 * Class FraudChequePurchaseVelocityTest
 * @package Tests\Unit\PurchaseGateway\Application\BI
 * @group   event-ingestion
 */
class FraudChequePurchaseVelocityTest extends UnitTestCase
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function cheque_event_should_be_created_successfully_from_fraud_purchase_velocity()
    {
        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed->method('toArray')->willReturn(
            [
                'memberInfo'            => [
                    'email'       => $this->faker->email,
                    'username'    => $this->faker->userName,
                    'firstName'   => $this->faker->firstName,
                    'lastName'    => $this->faker->lastName,
                    'countryCode' => $this->faker->countryCode,
                    'zipCode'     => $this->faker->postcode,
                    'address'     => $this->faker->address,
                    'city'        => $this->faker->city,
                ],
                'payment'               => [
                    'routingNumber' => $this->faker->numerify('######')
                ],
                'selectedCrossSells'    => [],
                'status'                => Transaction::STATUS_APPROVED,
                'initialAmount'         => $this->faker->randomFloat(2),
                'attemptedTransactions' => [
                    'submitAttempt' => $this->faker->numberBetween(1, 10),
                    'billerName'    => $this->faker->word
                ],
            ]
        );
        $ip          = $this->createMock(Ip::class);
        $paymentInfo = ChequePaymentInfo::build(ChequePaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $purchaseProcessed,
            $ip,
            $this->randomSite,
            $paymentInfo,
            null
        );
        $checkEvent  = FraudChequePurchaseVelocity::createFromVelocityEvent($event);
        $this->assertInstanceOf(FraudChequePurchaseVelocity::class, $checkEvent);
    }

    /**
     * @test
     * @return void
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function cheque_event_created_should_have_correct_routing_number_information()
    {
        $routingNumber     = $this->faker->numerify('######');
        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed->method('toArray')->willReturn(
            [
                'memberInfo'            => [
                    'email'       => $this->faker->email,
                    'username'    => $this->faker->userName,
                    'firstName'   => $this->faker->firstName,
                    'lastName'    => $this->faker->lastName,
                    'countryCode' => $this->faker->countryCode,
                    'zipCode'     => $this->faker->postcode,
                    'address'     => $this->faker->address,
                    'city'        => $this->faker->city,
                ],
                'payment'               => [
                    'routingNumber' => $routingNumber
                ],
                'selectedCrossSells'    => [],
                'status'                => Transaction::STATUS_APPROVED,
                'initialAmount'         => $this->faker->randomFloat(2),
                'attemptedTransactions' => [
                    'submitAttempt' => $this->faker->numberBetween(1, 10),
                    'billerName'    => $this->faker->word
                ],
            ]
        );

        $ip          = $this->createMock(Ip::class);
        $paymentInfo = ChequePaymentInfo::build(ChequePaymentInfo::PAYMENT_TYPE, null);
        $event       = FraudPurchaseVelocity::createFromPurchaseProcessed(
            $purchaseProcessed,
            $ip,
            $this->randomSite,
            $paymentInfo,
            null
        );
        $checkEvent  = FraudChequePurchaseVelocity::createFromVelocityEvent($event);
        $this->assertEquals($routingNumber, $checkEvent->jsonSerialize()['payment']['routingNumber']);
    }

}