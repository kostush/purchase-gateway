<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use Tests\UnitTestCase;

class FraudAdviceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function create_should_return_a_fraud_advice_object()
    {
        $this->assertInstanceOf(FraudAdvice::class, FraudAdvice::create(Ip::create('127.0.0.1')));
    }

    /**
     * @test
     * @return FraudAdvice
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     */
    public function add_process_fraud_advice_should_return_a_fraud_advice_object()
    {
        $initialFraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));
        $initialFraudAdvice->markInitCaptchaAdvised();
        $initialFraudAdvice->validateInitCaptcha();
        $initialFraudAdvice->markBlacklistedOnInit();
        $initialFraudAdvice->increaseTimesBlacklisted();

        $newFraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.2'),
            Email::create('test@gmain.com'),
            Zip::create('H0H0H0'),
            Bin::createFromString('123456')
        );

        $newFraudAdvice->markProcessCaptchaAdvised();
        $newFraudAdvice->validateProcessCaptcha();
        $newFraudAdvice->markBlacklistedOnProcess();

        $finalFraudAdvice = $initialFraudAdvice->addProcessFraudAdvice($newFraudAdvice);

        $this->assertInstanceOf(FraudAdvice::class, $finalFraudAdvice);

        return $finalFraudAdvice;
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_add_new_email_given(FraudAdvice $fraudAdvice)
    {
        $this->assertEquals('test@gmain.com', (string) $fraudAdvice->email());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_add_new_zip_given(FraudAdvice $fraudAdvice)
    {
        $this->assertEquals('H0H0H0', (string) $fraudAdvice->zip());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_add_new_bin_given(FraudAdvice $fraudAdvice)
    {
        $this->assertEquals('123456', (string) $fraudAdvice->bin());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_keep_init_advised_flag(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_keep_init_validated_flag(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isInitCaptchaValidated());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_keep_blacklisted_flag(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isBlacklistedOnInit());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_keep_captcha_already_validated(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isCaptchaAlreadyValidated());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_keep_times_blacklisted_count(FraudAdvice $fraudAdvice)
    {
        $this->assertEquals(1, $fraudAdvice->timesBlacklisted());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_add_captcha_for_process(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isProcessCaptchaAdvised());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function add_process_fraud_advice_should_add_blacklisted_for_process(FraudAdvice $fraudAdvice)
    {
        $this->assertTrue($fraudAdvice->isBlacklistedOnProcess());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function increase_times_blacklisted_should_increase_blacklisted_times_count(FraudAdvice $fraudAdvice)
    {
        $fraudAdvice->increaseTimesBlacklisted();

        $this->assertEquals(2, $fraudAdvice->timesBlacklisted());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function it_should_keep_force_three_d_flag_to_false(FraudAdvice $fraudAdvice)
    {
        $this->assertFalse($fraudAdvice->isForceThreeD());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function it_should_keep_detect_three_d_usage_flag_to_false(FraudAdvice $fraudAdvice)
    {
        $this->assertFalse($fraudAdvice->isDetectThreeDUsage());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function it_should_change_force_three_d_flag_to_true(FraudAdvice $fraudAdvice)
    {
        $fraudAdvice->markForceThreeDOnInit();
        $this->assertTrue($fraudAdvice->isForceThreeD());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function it_should_change_detect_three_d_usage_flag_to_true(FraudAdvice $fraudAdvice)
    {
        $fraudAdvice->markDetectThreeDUsage();
        $this->assertTrue($fraudAdvice->isDetectThreeDUsage());
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice FraudAdvice
     * @return void
     */
    public function it_should_have_all_key_when_to_array_is_called(FraudAdvice $fraudAdvice)
    {
        $fraudAdviceArray = $fraudAdvice->toArray();
        $allFound         = true;

        $keys = [
            'ip',
            'email',
            'zip',
            'bin',
            'initCaptchaAdvised',
            'initCaptchaValidated',
            'processCaptchaAdvised',
            'processCaptchaValidated',
            'blacklistedOnInit',
            'blacklistedOnProcess',
            'captchaAlreadyValidated',
            'timesBlacklisted',
            'forceThreeD',
            'detectThreeDUsage'
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $fraudAdviceArray)) {
                $allFound = false;
                break;
            }
        }

        $this->assertTrue($allFound);
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function mark_init_captcha_advised_should_mark_init_captcha_advised()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->markInitCaptchaAdvised();

        $this->assertTrue($fraudAdvice->isInitCaptchaAdvised());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validate_init_captcha_should_mark_init_captcha_validated()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->validateInitCaptcha();

        $this->assertTrue($fraudAdvice->isInitCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validate_init_captcha_should_mark_captcha_already_validated()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->validateInitCaptcha();

        $this->assertTrue($fraudAdvice->isCaptchaAlreadyValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function mark_process_captcha_advised_should_mark_process_captcha_advised()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->markProcessCaptchaAdvised();

        $this->assertTrue($fraudAdvice->isProcessCaptchaAdvised());
    }

    /**
     * @test
     * @return void
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validate_process_captcha_should_mark_process_captcha_validated()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->validateProcessCaptcha();

        $this->assertTrue($fraudAdvice->isProcessCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validate_process_captcha_should_mark_captcha_already_validated()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->validateProcessCaptcha();

        $this->assertTrue($fraudAdvice->isCaptchaAlreadyValidated());
    }

    /**
     * @test
     * @return void
     */
    public function validate_force_three_d_is_carried_on_from_previous_fraud_advice(): void
    {
        $oldFraudAdvice = FraudAdvice::create();
        $oldFraudAdvice->markForceThreeDOnInit();

        $newFraudAdvice = FraudAdvice::create();
        $newFraudAdvice->markForceThreeDOnInitBasedOnAdvice($oldFraudAdvice);

        $this->assertTrue($newFraudAdvice->isForceThreeDOnInit());
    }

    /**
     * @test
     * @return void
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function mark_process_captcha_validated_should_throw_exception_if_init_advised_but_not_validated()
    {
        $this->expectException(CannotValidateProcessCaptchaWithoutInitCaptchaException::class);

        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->markInitCaptchaAdvised();
        $fraudAdvice->validateProcessCaptcha();
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function mark_blacklisted_on_init_should_mark_blacklisted_on_init_on_advice()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->markBlacklistedOnInit();

        $this->assertTrue($fraudAdvice->isBlacklistedOnInit());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function mark_blacklisted_on_process_should_mark_blacklisted_on_process_on_advice()
    {
        $fraudAdvice = FraudAdvice::create(Ip::create('127.0.0.1'));

        $fraudAdvice->markBlacklistedOnProcess();

        $this->assertTrue($fraudAdvice->isBlacklistedOnProcess());
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws \ProBillerNG\Logger\Exception
     */
    public function fraud_fields_changed_should_return_true_if_fields_changed()
    {
        $fraudAdvice = FraudAdvice::create(null, Email::create('test@email.com'));
        $newEmail    = Email::create('testNew@email.com');

        $this->assertTrue($fraudAdvice->fraudFieldsChanged($newEmail, null, null));
    }

    /**
     * @test
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws \ProBillerNG\Logger\Exception
     */
    public function fraud_fields_changed_should_return_false_if_fields_not_changed()
    {
        $fraudAdvice = FraudAdvice::create(null, Email::create('test@email.com'));
        $sameEmail   = Email::create('test@email.com');

        $this->assertFalse($fraudAdvice->fraudFieldsChanged($sameEmail, null, null));
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice Fraud Advice
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function get_changed_fraud_fields_should_return_email_if_it_changed(FraudAdvice $fraudAdvice)
    {
        $this->assertArrayHasKey(
            'email',
            $fraudAdvice->getChangedFraudFields(
                Email::create('testNew@gmain.com'),
                Zip::create('H0H0H0'),
                Bin::createFromString('123456')
            )
        );
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice Fraud Advice
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function get_changed_fraud_fields_should_return_zip_if_it_changed(FraudAdvice $fraudAdvice)
    {
        $this->assertArrayHasKey(
            'zip',
            $fraudAdvice->getChangedFraudFields(
                Email::create('test@gmain.com'),
                Zip::create('H0H0H1'),
                Bin::createFromString('123456')
            )
        );
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice Fraud Advice
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function get_changed_fraud_fields_should_return_bin_if_it_changed(FraudAdvice $fraudAdvice)
    {
        $this->assertArrayHasKey(
            'bin',
            $fraudAdvice->getChangedFraudFields(
                Email::create('test@gmain.com'),
                Zip::create('H0H0H0'),
                Bin::createFromString('123457')
            )
        );
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice Fraud Advice
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function get_changed_fraud_fields_should_return_empty_array_if_nothing_changed(FraudAdvice $fraudAdvice)
    {
        $this->assertEquals(
            0,
            count(
                $fraudAdvice->getChangedFraudFields(
                    Email::create('test@gmain.com'),
                    Zip::create('H0H0H0'),
                    Bin::createFromString('123456')
                )
            )
        );
    }

    /**
     * @test
     * @depends add_process_fraud_advice_should_return_a_fraud_advice_object
     * @param FraudAdvice $fraudAdvice Fraud Advice
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function get_changed_fraud_fields_should_return_all_changed_fields_as_array(FraudAdvice $fraudAdvice)
    {
        $this->assertEquals(
            3,
            count(
                $fraudAdvice->getChangedFraudFields(
                    Email::create('testNew@gmain.com'),
                    Zip::create('H0H0H1'),
                    Bin::createFromString('123457')
                )
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ReflectionException
     */
    public function is_captcha_validated_should_return_true_if_captcha_already_validated()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $reflection = new \ReflectionClass(FraudAdvice::class);
        $attribute  = $reflection->getProperty('captchaAlreadyValidated');
        $attribute->setAccessible(true);

        $attribute->setValue($fraudAdvice, true);

        $this->assertTrue($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function is_captcha_validated_should_return_true_if_init_captcha_advised_and_validated_and_process_captcha_not_advised()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $fraudAdvice->markInitCaptchaAdvised();
        $fraudAdvice->validateInitCaptcha();

        $this->assertTrue($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function is_captcha_validated_should_return_true_if_init_captcha_advised_and_validated_and_process_captcha_advised_and_validated()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $fraudAdvice->markInitCaptchaAdvised();
        $fraudAdvice->validateInitCaptcha();
        $fraudAdvice->markProcessCaptchaAdvised();
        $fraudAdvice->validateProcessCaptcha();

        $this->assertTrue($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function is_captcha_validated_should_return_true_if_init_captcha_not_advised_and_process_captcha_advised_and_validated()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $fraudAdvice->markProcessCaptchaAdvised();
        $fraudAdvice->validateProcessCaptcha();

        $this->assertTrue($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function is_captcha_validated_should_return_true_if_init_captcha_not_advised_and_process_captcha_not_advised()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $this->assertTrue($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function is_captcha_validated_should_return_false_if_init_captcha_advised_and_not_validated()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $fraudAdvice->markInitCaptchaAdvised();

        $this->assertFalse($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     * @throws InvalidIpException
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function is_captcha_validated_should_return_false_if_process_captcha_advised_and_not_validated()
    {
        $fraudAdvice = FraudAdvice::create(
            Ip::create('127.0.0.3'),
            Email::create('testNew@gmain.com'),
            Zip::create('H0H0H1'),
            Bin::createFromString('123457')
        );

        $fraudAdvice->markProcessCaptchaAdvised();

        $this->assertFalse($fraudAdvice->isCaptchaValidated());
    }

    /**
     * @test
     * @return void
     */
    public function should_block_process_should_return_true_if_blacklisted_on_init()
    {
        $fraudAdvice = FraudAdvice::create();

        $fraudAdvice->markBlacklistedOnInit();

        $this->assertTrue($fraudAdvice->shouldBlockProcess());
    }

    /**
     * @test
     * @return void
     */
    public function should_block_process_should_return_true_if_blacklisted_on_process_more_than_5_times()
    {
        $fraudAdvice = FraudAdvice::create();

        $fraudAdvice->markBlacklistedOnProcess();

        $fraudAdvice->increaseTimesBlacklisted();
        $fraudAdvice->increaseTimesBlacklisted();
        $fraudAdvice->increaseTimesBlacklisted();
        $fraudAdvice->increaseTimesBlacklisted();
        $fraudAdvice->increaseTimesBlacklisted();

        $this->assertTrue($fraudAdvice->shouldBlockProcess());
    }

    /**
     * @test
     * @return void
     */
    public function should_block_process_should_return_true_if_captcha_not_validated()
    {
        $fraudAdvice = FraudAdvice::create();

        $fraudAdvice->markInitCaptchaAdvised();

        $this->assertTrue($fraudAdvice->shouldBlockProcess());
    }

    /**
     * @test
     * @return void
     */
    public function should_block_process_should_return_true_if_blacklisted_on_process_but_client_still_has_attempts_left()
    {
        $fraudAdvice = FraudAdvice::create();

        $fraudAdvice->markBlacklistedOnProcess();

        $this->assertFalse($fraudAdvice->shouldBlockProcess());
    }

    /**
     * @test
     * @return void
     */
    public function should_block_process_should_return_true_if_no_blacklist_or_captcha()
    {
        $fraudAdvice = FraudAdvice::create();

        $this->assertFalse($fraudAdvice->shouldBlockProcess());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_block_process_after_two_attempts_returning_blacklist(): void
    {
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markBlacklistedOnProcess();
        $fraudAdvice->increaseTimesBlacklisted();
        $fraudAdvice->increaseTimesBlacklisted();

        $this->assertTrue($fraudAdvice->shouldBlockProcess());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_block_process_if_black_list_is_one_time(): void
    {
        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markBlacklistedOnProcess();
        $fraudAdvice->increaseTimesBlacklisted();

        $this->assertTrue($fraudAdvice->shouldBlockProcess());
    }
}
