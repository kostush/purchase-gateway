<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseBiEventFactory;
use ProBillerNG\PurchaseGateway\Application\BI\PurchasePending;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use Tests\UnitTestCase;
use Throwable;

class PurchaseBiEventFactoryTest extends UnitTestCase
{
    private $biCheckPaymentData = [
        'routingNumber'       => ObfuscatedData::OBFUSCATED_STRING,
        'accountNumber'       => ObfuscatedData::OBFUSCATED_STRING,
        'socialSecurityLast4' => ObfuscatedData::OBFUSCATED_STRING
    ];

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidCreditCardExpirationDate
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws Throwable
     */
    public function create_for_new_cc_should_return_a_purchase_processed_event_when_state_not_pending(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween(2030, 2040),
                null
            )
        );

        $event = PurchaseBiEventFactory::createForNewCC($purchaseProcess, true);

        $this->assertInstanceOf(PurchaseProcessed::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidCreditCardExpirationDate
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws Throwable
     */
    public function create_for_new_cc_should_return_a_purchase_pending_event_when_state_is_pending(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchaseProcess->setPaymentInfo(
            NewCCPaymentInfo::create(
                (string) $this->faker->creditCardNumber,
                (string) $this->faker->numberBetween(100, 999),
                (string) $this->faker->numberBetween(1, 12),
                (string) $this->faker->numberBetween(2030, 2040),
                null
            )
        );

        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->startPending();

        $event = PurchaseBiEventFactory::createForNewCC($purchaseProcess, true);

        $this->assertInstanceOf(PurchasePending::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidCurrency
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException|UnknownBillerNameException
     */
    public function create_for_payment_template_should_return_a_purchase_processed_event_when_state_not_pending(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplate           = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('isSelected')->willReturn(true);
        $paymentTemplate->method('firstSix')->willReturn("123456");
        $paymentTemplate->method('lastFour')->willReturn("1234");
        $paymentTemplate->method('billerFields')->willReturn([]);

        $paymentTemplateCollection->add($paymentTemplate);

        $purchaseProcess->setPaymentTemplateCollection($paymentTemplateCollection);

        $event = PurchaseBiEventFactory::createForPaymentTemplate($purchaseProcess, true);

        $this->assertInstanceOf(PurchaseProcessed::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    public function create_for_payment_template_should_return_a_purchase_pending_event_when_state_is_pending(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplate           = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('isSelected')->willReturn(true);
        $paymentTemplate->method('firstSix')->willReturn("123456");
        $paymentTemplate->method('lastFour')->willReturn("1234");
        $paymentTemplate->method('billerFields')->willReturn([]);

        $paymentTemplateCollection->add($paymentTemplate);

        $purchaseProcess->setPaymentTemplateCollection($paymentTemplateCollection);
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->startPending();

        $event = PurchaseBiEventFactory::createForPaymentTemplate($purchaseProcess, true);

        $this->assertInstanceOf(PurchasePending::class, $event);
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws UnknownBillerNameException
     * @throws \Exception
     */
    public function create_for_new_check_should_return_a_purchase_processed_event_when_state_not_pending(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $accountNumber = (string) $this->faker->numberBetween(100, 999);
        $routingNumber = (string) $this->faker->numberBetween(100, 999);
        $socialSecurityLast4 = (string) $this->faker->numberBetween(2030, 2040);

        $purchaseProcess->setPaymentInfo(
            ChequePaymentInfo::create(
                $routingNumber,
                $accountNumber,
                false,
                $socialSecurityLast4,
                ChequePaymentInfo::PAYMENT_TYPE,
                ChequePaymentInfo::PAYMENT_METHOD
            )
        );

        $event = PurchaseBiEventFactory::createForCheck($purchaseProcess);

        $this->assertInstanceOf(PurchaseProcessed::class, $event);

        $this->biCheckPaymentData['accountNumber'] = ChequePaymentInfo::obfuscateAccountNumber($accountNumber);
        $this->biCheckPaymentData['routingNumber'] = $routingNumber;
        $this->biCheckPaymentData['socialSecurityLast4'] = $socialSecurityLast4;

        $this->assertEqualsCanonicalizing(
            $this->biCheckPaymentData,
            $event->toArray()['payment']
        );
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws Throwable
     */
    public function create_for_new_check_should_return_a_purchase_pending_event_when_state_is_pending(): void
    {
        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $accountNumber = (string) $this->faker->numberBetween(100, 999);
        $routingNumber = (string) $this->faker->numberBetween(100, 999);
        $socialSecurityLast4 = (string) $this->faker->numberBetween(2030, 2040);

        $purchaseProcess->setPaymentInfo(
            ChequePaymentInfo::create(
                $routingNumber,
                $accountNumber,
                false,
                $socialSecurityLast4,
                ChequePaymentInfo::PAYMENT_TYPE,
                ChequePaymentInfo::PAYMENT_METHOD
            )
        );

        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->startPending();

        $event = PurchaseBiEventFactory::createForCheck($purchaseProcess);
        $this->assertInstanceOf(PurchasePending::class, $event);
        
        $this->biCheckPaymentData['routingNumber'] = $routingNumber;
        $this->biCheckPaymentData['accountNumber'] = ChequePaymentInfo::obfuscateAccountNumber($accountNumber);
        $this->biCheckPaymentData['socialSecurityLast4'] = $socialSecurityLast4;

        $this->assertEqualsCanonicalizing(
            $this->biCheckPaymentData,
            $event->toArray()['payment']
        );
    }
}
