<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\BillerNotSupportedException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewChequePerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\ApiException;
use Tests\UnitTestCase;

class NewChequePerformTransactionAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws ApiException
     * @throws BillerNotSupportedException
     * @throws Exception
     * @throws InvalidResponseException
     */
    public function it_should_throw_an_exception_when_biller_is_not_rocketgate(): void
    {
        $this->expectException(BillerNotSupportedException::class);

        $adapter = new NewChequePerformTransactionAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $adapter->performTransaction(
            SiteId::createFromString($this->faker->uuid),
           new NetbillingBiller(),
            CurrencyCode::USD(),
            $this->createMock(UserInfo::class),
            $this->createMock(ChargeInformation::class),
            $this->createMock(PaymentInfo::class),
            $this->createMock(BillerMapping::class),
            $this->createMock(SessionId::class),
            false
        );
    }

    /**
     * @test
     * @return void
     * @throws BillerNotSupportedException
     * @throws Exception
     * @throws InvalidAmountException
     * @throws InvalidIpException
     * @throws InvalidPaymentInfoException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws InvalidResponseException
     * @throws ApiException
     */
    public function new_cheque_rocketgate_request_should_have_member_information_and_cheque_information(): void
    {
        $adapter = new NewChequePerformTransactionAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $userInfo = UserInfo::create(
            CountryCode::create('US'),
            Ip::create('10.10.10.10'),
            Email::create($this->faker->email),
            Username::create('test1234'),
            Password::create('pass12345'),
            FirstName::create($this->faker->firstName),
            LastName::create($this->faker->lastName),
            Zip::create('80085'),
            $this->faker->city,
            $this->faker->city,
            PhoneNumber::create('514-000-0911'),
            $this->faker->address

        );

        $billerFields = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_3'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_3'],
            '8000',
            'sharedSecret',
            true
        );

        $billeMapping = $this->createMock(BillerMapping::class);
        $billeMapping->method('billerFields')
            ->willReturn($billerFields);

        $paymentInfo = ChequePaymentInfo::create(
            '99999999',
            '56789965',
            false,
            '1112',
            'checks',
            'checks'
        );

        $result = $adapter->performTransaction(
            SiteId::createFromString($this->faker->uuid),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $userInfo,
            BundleSingleChargeInformation::create(Amount::create(10), Duration::create(10)),
            $paymentInfo,
            $billeMapping,
            $this->createMock(SessionId::class),
            false
        );

        $this->assertInstanceOf(NewChequePerformTransactionAdapter::class, $adapter);
    }
}