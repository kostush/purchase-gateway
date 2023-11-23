<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ThirdPartyPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use Tests\IntegrationTestCase;

class ThirdPartyPaymentProcessCommandHandlerTest extends IntegrationTestCase
{
    public const USER_INFO = [
        'address'     => 'Address',
        'city'        => 'City',
        'state'       => 'State',
        'username'    => 'username',
        'password'    => 'password',
        'email'       => 'email@test.mindgeek.com',
        'firstName'   => 'Firstname',
        'lastName'    => 'Lastname',
        'zip'         => 'h1h1h1',
        'phoneNumber' => '5140000912',
        'country'     => 'CA',
    ];

    /**
     * @test
     * @return array Response
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_return_a_purchase_process_dto(): array
    {
        $sessionId = $this->createMock(SessionId::class);

        $paymentInfo = $this->createMock(PaymentInfo::class);
        $paymentInfo->method('paymentType')->willReturn('banktransfer');

        $userInfo = $this->getMockBuilder(UserInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mainItem = $this->createMock(InitializedItem::class);

        $state = $this->createMock(Valid::class);

        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'paymentInfo',
                    'redirectUrl',
                    'isValid',
                    'wasMemberIdGenerated',
                    'userInfo',
                    'isFraud',
                    'sessionId',
                    'initializedItemCollection',
                    'retrieveMainPurchaseItem',
                    'state',
                    'cascade',
                    'publicKeyIndex'
                ]
            )
            ->getMock();

        $purchaseProcess->method('redirectUrl')->willReturn('https://redirect.url');
        $purchaseProcess->method('isValid')->willReturn(TRUE);
        $purchaseProcess->method('wasMemberIdGenerated')->willReturn(TRUE);
        $purchaseProcess->method('paymentInfo')->willReturn($paymentInfo);
        $purchaseProcess->method('userInfo')->willReturn($userInfo);
        $purchaseProcess->method('isFraud')->willReturn(FALSE);
        $purchaseProcess->method('sessionId')->willReturn($sessionId);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($mainItem);
        $purchaseProcess->method('state')->willReturn($state);
        $purchaseProcess->method('cascade')->willReturn(Cascade::create(BillerCollection::buildBillerCollection([new QyssoBiller()])));
        $purchaseProcess->method('publicKeyIndex')->willReturn(0);

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn($purchaseProcess);

        $tokenGenerator          = new JsonWebTokenGenerator();
        $site                    = $this->createSite();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

        $handler = new ThirdPartyPaymentProcessCommandHandler($processHandler, $httpCommandDTOAssembler);

        $command = $this->createProcessCommand();

        $dto = $handler->execute($command);

        self::assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);

        return $dto->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response
     */
    public function it_should_contain_success_with_true_value(array $response): void
    {
        self::assertTrue($response['success']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response
     */
    public function it_should_contain_redirect_to_url_as_next_action(array $response): void
    {
        self::assertSame('redirectToUrl', $response['nextAction']['type']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response
     */
    public function it_should_contain_third_party_url_in_next_action(array $response): void
    {
        self::assertNotNull($response['nextAction']['thirdParty']['url']);
    }

    /**
     * @test
     * @return UserInfo
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done(): UserInfo
    {
        $paymentInfo = $this->createMock(PaymentInfo::class);
        $paymentInfo->method('paymentType')->willReturn('banktransfer');

        $userInfo = UserInfo::create(
            CountryCode::create($this->faker->countryCode),
            Ip::create($this->faker->ipv4)
        );

        $state = $this->createMock(Valid::class);

        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'paymentInfo',
                    'redirectUrl',
                    'isValid',
                    'wasMemberIdGenerated',
                    'userInfo',
                    'isFraud',
                    'sessionId',
                    'initializedItemCollection',
                    'retrieveMainPurchaseItem',
                    'state',
                    'cascade',
                    'publicKeyIndex'
                ]
            )
            ->getMock();

        $purchaseProcess->method('redirectUrl')->willReturn('https://redirect.url');
        $purchaseProcess->method('isValid')->willReturn(true);
        $purchaseProcess->method('wasMemberIdGenerated')->willReturn(false);
        $purchaseProcess->method('paymentInfo')->willReturn($paymentInfo);
        $purchaseProcess->method('userInfo')->willReturn($userInfo);
        $purchaseProcess->method('state')->willReturn($state);
        $purchaseProcess->method('cascade')->willReturn(
            Cascade::create(
                BillerCollection::buildBillerCollection(
                    [new QyssoBiller()]
                )
            )
        );
        $purchaseProcess->method('publicKeyIndex')->willReturn(0);


        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn($purchaseProcess);

        $tokenGenerator          = new JsonWebTokenGenerator();
        $site                    = $this->createSite();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

        $handler = new ThirdPartyPaymentProcessCommandHandler($processHandler, $httpCommandDTOAssembler);

        $command = $this->createProcessCommand(self::USER_INFO);

        $handler->execute($command);

        self::assertSame((string) $userInfo->email(), self::USER_INFO['email']);

        return $userInfo;
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_address_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->address(), self::USER_INFO['address']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_city_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->city(), self::USER_INFO['city']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_state_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->state(), self::USER_INFO['state']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_username_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->username(), self::USER_INFO['username']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_password_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->password(), self::USER_INFO['password']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_first_name_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->firstName(), self::USER_INFO['firstName']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_last_name_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->lastName(), self::USER_INFO['lastName']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_zip_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->zipCode(), self::USER_INFO['zip']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_phone_number_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->phoneNumber(), self::USER_INFO['phoneNumber']);
    }

    /**
     * @test
     * @depends it_should_update_user_with_new_email_if_a_secrev_for_qysso_is_done
     * @param UserInfo $userInfo User info
     * @return void
     */
    public function it_should_update_user_with_new_country_code_if_a_secrev_for_qysso_is_done(UserInfo $userInfo): void
    {
        self::assertSame((string) $userInfo->countryCode(), self::USER_INFO['country']);
    }
}
