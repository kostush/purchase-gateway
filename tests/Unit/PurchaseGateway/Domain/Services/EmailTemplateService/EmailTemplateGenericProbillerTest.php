<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\EmailTemplateService;

use Faker\Provider\Base;
use Faker\Provider\Lorem;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates\EmailTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates\EmailTemplateGenericProbiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use Tests\UnitTestCase;

class EmailTemplateGenericProbillerTest extends UnitTestCase
{
    public const DEFAULT_TAX_RATE_with_PERCENTAGE = '0.00%';

    /** @var array */
    private $payload;

    /** @var MockObject|Site */
    private $site;

    /** @var MockObject|PurchaseProcessed */
    private $purchaseProcessed;

    /** @var MockObject|RetrieveTransactionResult */
    private $retrieveTransactionResult;

    /** @var MockObject|NewCCTransactionInformation */
    private $transactionInformation;

    /** @var string|null */
    private $first6;

    /** @var string|null */
    private $last4;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->first6 = '123456';
        $this->last4  = '7788';

        $this->payload = [
            'REAL_NAME1'           => 'Mister',
            'REAL_NAME2'           => 'Axe',
            'SITE_NAME'            => 'test site name',
            'DESCRIPTOR_NAME'      => 'test descriptor',
            'CURRENCY'             => (string) CurrencyCode::USD(),
            'CURRENCYSYMBOL'       => CurrencyCode::symbolByCode((string) CurrencyCode::USD()),
            'CARD_NUMBER_XXX'      => $this->first6 . 'XXXXXX' . $this->last4,
            'REMOTE_ADDR'          => null,
            'TAXNAME'              => 'VAT',
            'TAXRATE'              => '0.05',
            'online_support_link'  => 'test supportLink',
            'call_support_link'    => 'test phoneNumber',
            'mail_support_link'    => 'test mailSupportLink',
            'message_support_link' => 'test messageSupportLink',
            'support_cancellation_link' => 'test cancellationSupportLink',
            'skype_support_link'   => 'test skypeNumber',
        ];

        $this->site = $this->createMock(Site::class);
        $this->site->method('name')->willReturn($this->payload['SITE_NAME']);
        $this->site->method('descriptor')->willReturn($this->payload['DESCRIPTOR_NAME']);
        $this->site->method('supportLink')->willReturn($this->payload['online_support_link']);
        $this->site->method('phoneNumber')->willReturn($this->payload['call_support_link']);
        $this->site->method('mailSupportLink')->willReturn($this->payload['mail_support_link']);
        $this->site->method('messageSupportLink')->willReturn($this->payload['message_support_link']);
        $this->site->method('cancellationLink')->willReturn($this->payload['support_cancellation_link']);
        $this->site->method('skypeNumber')->willReturn($this->payload['skype_support_link']);

        $this->purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $this->purchaseProcessed
            ->method('ipAddress')->willReturn($this->payload['REMOTE_ADDR']);
        $this->purchaseProcessed
            ->method('amounts')->willReturn(
                [
                    'taxName' => $this->payload['TAXNAME'],
                    'taxRate' => $this->payload['TAXRATE']
                ]
            );
        $this->purchaseProcessed
            ->method('memberInfo')->willReturn(
                [
                    'firstName' => $this->payload['REAL_NAME1'],
                    'lastName'  => $this->payload['REAL_NAME2']
                ]
            );

        $this->transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $this->transactionInformation->method('first6')->willReturn($this->first6);
        $this->transactionInformation->method('last4')->willReturn($this->last4);

        $this->retrieveTransactionResult = $this->createMock(RetrieveTransactionResult::class);
        $this->retrieveTransactionResult->method('currency')->willReturn($this->payload['CURRENCY']);
        $this->retrieveTransactionResult
            ->method('transactionInformation')->willReturn($this->transactionInformation);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    private function addLoginPayload(): void
    {
        $this->payload = array_merge(
            $this->payload,
            [
                'LOGIN_NAME' => 'username',
                'SITE_URL'   => 'test site url'
            ]
        );

        $this->purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $this->purchaseProcessed
            ->method('ipAddress')->willReturn($this->payload['REMOTE_ADDR']);
        $this->purchaseProcessed
            ->method('amounts')->willReturn(
                [
                    'taxName' => $this->payload['TAXNAME'],
                    'taxRate' => $this->payload['TAXRATE']
                ]
            );

        $this->purchaseProcessed->method('memberInfo')->willReturn(
            [
                'firstName' => $this->payload['REAL_NAME1'],
                'lastName'  => $this->payload['REAL_NAME2'],
                'username'  => $this->payload['LOGIN_NAME']
            ]
        );
        $this->site->method('url')->willReturn($this->payload['SITE_URL']);
    }

    /**
     * @throws \Exception
     * @throws \ReflectionException
     * @return void
     */
    private function addTransactionPayload(): void
    {
        $this->payload = array_merge(
            $this->payload,
            [
                'transactionId' => '1234',
                'occurredOn'    => new \DateTimeImmutable(),
                'AMOUNTPRETAX'  => '1',
                'AMOUNTTAXONLY' => '2',
                'AMOUNTWITHTAX' => '3'
            ]
        );

        $this->transactionInformation->method('transactionId')->willReturn($this->payload['transactionId']);

        $this->purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $this->purchaseProcessed
            ->method('lastTransactionId')->willReturn($this->payload['transactionId']);
        $this->purchaseProcessed
            ->method('ipAddress')->willReturn($this->payload['REMOTE_ADDR']);

        $this->purchaseProcessed->method('occurredOn')->willReturn($this->payload['occurredOn']);

        $this->purchaseProcessed
            ->method('amounts')->willReturn(
                [
                    'taxName'       => $this->payload['TAXNAME'],
                    'taxRate'       => $this->payload['TAXRATE'],
                    'initialAmount' => [
                        'beforeTaxes' => $this->payload['AMOUNTPRETAX'],
                        'taxes'       => $this->payload['AMOUNTTAXONLY'],
                        'afterTaxes'  => $this->payload['AMOUNTWITHTAX'],
                    ]
                ]
            );

        $this->purchaseProcessed->method('memberInfo')->willReturn(
            [
                'firstName' => $this->payload['REAL_NAME1'],
                'lastName'  => $this->payload['REAL_NAME2']
            ]
        );

        $this->transactionInformation->method('amount')->willReturn((float) 1);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     * @return void
     */
    private function addRebillPayload(): void
    {
        $this->payload = array_merge(
            $this->payload,
            [
                'transactionId'                 => '1234',
                'occurredOn'                    => new \DateTimeImmutable(),
                'initialDays'                   => 256,
                'NEXT_REBILLING_AMOUNT_PRETAX'  => 1,
                'NEXT_REBILLING_AMOUNT_TAXONLY' => 2,
                'NEXT_REBILLING_AMOUNT_WITHTAX' => 3,
            ]
        );

        $this->transactionInformation->method('transactionId')->willReturn($this->payload['transactionId']);

        $this->purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $this->purchaseProcessed
            ->method('lastTransactionId')->willReturn($this->payload['transactionId']);
        $this->purchaseProcessed
            ->method('ipAddress')->willReturn($this->payload['REMOTE_ADDR']);

        $this->purchaseProcessed
            ->method('occurredOn')->willReturn($this->payload['occurredOn']);

        $this->purchaseProcessed
            ->method('initialDays')->willReturn($this->payload['initialDays']);

        $this->purchaseProcessed
            ->method('amounts')->willReturn(
                [
                    'taxName'      => $this->payload['TAXNAME'],
                    'taxRate'      => $this->payload['TAXRATE'],
                    'rebillAmount' => [
                        'beforeTaxes' => $this->payload['NEXT_REBILLING_AMOUNT_PRETAX'],
                        'taxes'       => $this->payload['NEXT_REBILLING_AMOUNT_TAXONLY'],
                        'afterTaxes'  => $this->payload['NEXT_REBILLING_AMOUNT_WITHTAX'],
                    ]
                ]
            );

        $this->purchaseProcessed->method('memberInfo')->willReturn(
            [
                'firstName' => $this->payload['REAL_NAME1'],
                'lastName'  => $this->payload['REAL_NAME2']
            ]
        );

        $this->transactionInformation->method('amount')->willReturn((float) 0);
    }

    /**
     * @test
     * @return EmailTemplate $emailTemplate
     * @throws \Exception
     */
    public function it_should_return_a_generic_template_when_given_a_valid_payload(): EmailTemplate
    {
        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn($this->first6);
        $paymentTemplate->method('lastFour')->willReturn($this->last4);

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            $paymentTemplate
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        return $emailTemplate;
    }

    /**
     * @test
     * @return EmailTemplate $emailTemplate
     * @throws \Exception
     */
    public function it_should_return_a_generic_template_when_given_a_valid_cross_sale_payload(): EmailTemplate
    {

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn($this->first6);
        $paymentTemplate->method('lastFour')->willReturn($this->last4);

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            $paymentTemplate,
            ['initialDays' => 4]
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        return $emailTemplate;
    }

    /**
     * @test
     * @return void $emailTemplate
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoUsername
     */
    public function it_should_return_generic_template_with_account_number_when_purchase_payment_type_is_checks(): void
    {
        $accountNumber = "12345";
        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $paymentTemplate = null;

        $this->retrieveTransactionResult
            ->method('paymentType')
            ->willReturn(ChequePaymentInfo::PAYMENT_TYPE);

        $this->purchaseProcessed->method('payment')->willReturn(
            [
                "accountNumber"       => $accountNumber
            ]
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            $paymentTemplate
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        $this->assertEquals($accountNumber, $emailTemplate->templateData()['ACCT_NUMBER_XXX']);
        $this->assertArrayNotHasKey('CARD_NUMBER_XXX', $emailTemplate->templateData());
    }


    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_real_name_1_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals($this->payload['REAL_NAME1'], $emailTemplate->templateData()['REAL_NAME1']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_real_name_2_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals($this->payload['REAL_NAME2'], $emailTemplate->templateData()['REAL_NAME2']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_site_name_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals($this->payload['SITE_NAME'], $emailTemplate->templateData()['SITE_NAME']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_descriptor_name_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals($this->payload['DESCRIPTOR_NAME'], $emailTemplate->templateData()['DESCRIPTOR_NAME']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_CURRENCYSYMBOL_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals($this->payload['CURRENCYSYMBOL'], $emailTemplate->templateData()['CURRENCYSYMBOL']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_currency_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals($this->payload['CURRENCY'], $emailTemplate->templateData()['CURRENCY']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_card_number_xxx_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals($this->payload['CARD_NUMBER_XXX'], $emailTemplate->templateData()['CARD_NUMBER_XXX']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_not_contain_account_number_from_credit_card_purchase(EmailTemplate $emailTemplate): void
    {
        $this->assertArrayNotHasKey('ACCT_NUMBER_XXX', $emailTemplate->templateData());
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_remote_addr_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals($this->payload['REMOTE_ADDR'], $emailTemplate->templateData()['REMOTE_ADDR']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_taxname_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals($this->payload['TAXNAME'], $emailTemplate->templateData()['TAXNAME']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_tax_rate_if_valid_payload_is_given(EmailTemplate $emailTemplate): void
    {
        $this->assertEquals(number_format((float)$this->payload['TAXRATE']*100, 2, '.', '').'%', $emailTemplate->templateData()['TAXRATE']);
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_online_support_link_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals(
            $this->payload['online_support_link'],
            $emailTemplate->templateData()['online_support_link']
        );
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_call_support_link_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals(
            'tel:' . $this->payload['call_support_link'],
            $emailTemplate->templateData()['call_support_link']
        );
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_mail_support_link_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals(
            'mailto:' . $this->payload['mail_support_link'],
            $emailTemplate->templateData()['mail_support_link']
        );
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_message_support_link_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals(
            $this->payload['message_support_link'],
            $emailTemplate->templateData()['message_support_link']
        );
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_cancellation_link_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals(
            $this->payload['support_cancellation_link'],
            $emailTemplate->templateData()['support_cancellation_link']
        );
    }

    /**
     * @test
     * @param EmailTemplate $emailTemplate EmailTemplate
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @return void
     */
    public function it_should_contain_a_valid_skype_support_link_if_valid_payload_is_given(
        EmailTemplate $emailTemplate
    ): void {
        $this->assertEquals(
            $this->payload['skype_support_link'],
            $emailTemplate->templateData()['skype_support_link']
        );
    }

    /**
     * @test
     * @return array
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @throws \Exception
     */
    public function it_should_create_an_email_template_with_login_block_if_valid_payload_is_given(): array
    {
        $this->addLoginPayload();

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        return [$emailTemplate, $this->payload];
    }

    /**
     * @test
     * @depends it_should_create_an_email_template_with_login_block_if_valid_payload_is_given
     * @param array $objectAndPayload EmailTemplate
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_contain_login_name_if_valid_payload_is_given(array $objectAndPayload): void
    {
        $this->addLoginPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;

        $this->assertEquals($payload['LOGIN_NAME'], $object->templateData()['LOGIN_NAME']);
    }

    /**
     * @test
     * @depends it_should_create_an_email_template_with_login_block_if_valid_payload_is_given
     * @param array $objectAndPayload EmailTemplate
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_contain_site_url_if_valid_payload_is_given(array $objectAndPayload): void
    {
        $this->addLoginPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['SITE_URL'], $object->templateData()['SITE_URL']);
    }

    /**
     * @test
     * @return array
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @throws \Exception
     */
    public function it_should_create_an_email_template_with_transaction_block_if_valid_payload_is_given(): array
    {
        $this->addTransactionPayload();

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        return [$emailTemplate, $this->payload];
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @depends it_should_create_an_email_template_with_transaction_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     */
    public function it_should_contain_transaction_date_if_given_valid_payload(array $objectAndPayload): void
    {
        $this->addTransactionPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            (string) $payload['occurredOn']->format('Y-m-d'),
            $object->templateData()['TRANSACTION_DATE']
        );
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @depends it_should_create_an_email_template_with_transaction_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     */
    public function it_should_contain_amount_pretax_if_given_valid_payload(array $objectAndPayload): void
    {
        $this->addTransactionPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['AMOUNTPRETAX'], $object->templateData()['AMOUNTPRETAX']);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @depends it_should_create_an_email_template_with_transaction_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     */
    public function it_should_contain_amount_taxonly_if_given_valid_payload(array $objectAndPayload): void
    {
        $this->addTransactionPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['AMOUNTTAXONLY'], $object->templateData()['AMOUNTTAXONLY']);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @depends it_should_create_an_email_template_with_transaction_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     */
    public function it_should_contain_amount_with_tax_if_given_valid_payload(array $objectAndPayload): void
    {
        $this->addTransactionPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['AMOUNTWITHTAX'], $object->templateData()['AMOUNTWITHTAX']);
    }

    /**
     * @test
     * @return array
     * @depends it_should_return_a_generic_template_when_given_a_valid_payload
     * @throws \Exception
     */
    public function it_should_create_an_email_template_with_rebill_block_if_valid_payload_is_given(): array
    {
        $this->addRebillPayload();

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        return [$emailTemplate, $this->payload];
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_create_an_email_template_with_rebill_block_and_correct_initial_days_if_valid_cross_sale_payload_is_given(): void
    {
        $this->addRebillPayload();

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null,
            ['initialDays' => 2]
        );

        $this->assertEquals(2,$emailTemplate->templateData()['INITIAL_DAYS']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_contain_correct_initial_days_for_cross_sale_when_main_purchase_has_no_rebill(): void
    {
        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null,
            ['initialDays' => 5]
        );

        $this->assertEquals(5,$emailTemplate->templateData()['INITIAL_DAYS']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_contain_correct_initial_days_for_main_purchase_when_there_is_no_cross_sale(): void
    {
        $expectedInitialDays = 200;

        $this->purchaseProcessed->method('initialDays')
            ->willReturn($expectedInitialDays);

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null
        );

        $this->assertEquals($expectedInitialDays, $emailTemplate->templateData()['INITIAL_DAYS']);
    }

    /**
     * @test
     * @return array
     * @depends it_should_return_a_generic_template_when_given_a_valid_cross_sale_payload
     * @throws \Exception
     */
    public function it_should_create_an_email_template_with_rebill_block_if_valid_cross_sale_payload_is_given(): array
    {
        $this->addRebillPayload();

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null,
            ['initialDays' => 4]
        );

        $this->assertInstanceOf(
            EmailTemplateGenericProbiller::class,
            $emailTemplate
        );

        return [$emailTemplate, $this->payload];
    }

    /**
     * @test
     * @depends it_should_create_an_email_template_with_rebill_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     * @throws \Exception
     * @return void
     */
    public function it_should_contain_rebilling_due_date_if_valid_payload_is_given(array $objectAndPayload): void
    {
        $this->addRebillPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            (string) $payload['occurredOn']
                ->add(new \DateInterval('P' . $payload['initialDays'] . 'D'))
                ->format('Y-m-d'),
            $object->templateData()['REBILLING_DUE_DATE']
        );
    }


    /**
     * @test
     * @depends it_should_create_an_email_template_with_rebill_block_if_valid_cross_sale_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     * @throws \Exception
     * @return void
     */
    public function it_should_contain_rebilling_due_date_if_valid_payload_and_cross_sale_is_given(array $objectAndPayload): void
    {
        $this->addRebillPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            (string) $payload['occurredOn']
                ->add(new \DateInterval('P' . 4 . 'D'))
                ->format('Y-m-d'),
            $object->templateData()['REBILLING_DUE_DATE']
        );
    }

    /**
     * @test
     * @depends it_should_create_an_email_template_with_rebill_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_contain_next_rebilling_amount_pretax_if_valid_payload_is_given(
        array $objectAndPayload
    ): void {
        $this->addRebillPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            $payload['NEXT_REBILLING_AMOUNT_PRETAX'],
            $object->templateData()['NEXT_REBILLING_AMOUNT_PRETAX']
        );
    }

    /**
     * @test
     * @depends it_should_create_an_email_template_with_rebill_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_contain_next_rebilling_amount_taxonly_if_valid_payload_is_given(
        array $objectAndPayload
    ): void {
        $this->addRebillPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            $payload['NEXT_REBILLING_AMOUNT_TAXONLY'],
            $object->templateData()['NEXT_REBILLING_AMOUNT_TAXONLY']
        );
    }

    /**
     * @test
     * @depends it_should_create_an_email_template_with_rebill_block_if_valid_payload_is_given
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_contain_next_rebilling_amount_withtax_if_valid_payload_is_given(
        array $objectAndPayload
    ): void {
        $this->addRebillPayload();

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            $payload['NEXT_REBILLING_AMOUNT_WITHTAX'],
            $object->templateData()['NEXT_REBILLING_AMOUNT_WITHTAX']
        );
    }

    /**
     * @param array $payload Payload
     * @param float $amount  Amount
     * @param null  $tax     Tax
     * @return MockObject|PurchaseProcessed
     */
    private function create_purchase_processed($payload, $amount, $tax = null)
    {
        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed->method('lastTransactionId')->willReturn($payload['transactionId']);
        $purchaseProcessed->method('ipAddress')->willReturn($payload['REMOTE_ADDR']);
        $purchaseProcessed->method('occurredOn')->willReturn($payload['occurredOn']);
        $purchaseProcessed->method('amounts')->willReturn($tax);
        $purchaseProcessed->method('amount')->willReturn($amount);
        $purchaseProcessed->method('memberInfo')->willReturn(
            [
                'firstName' => $payload['REAL_NAME1'],
                'lastName'  => $payload['REAL_NAME2']
            ]
        );
        return $purchaseProcessed;
    }

    /**
     * @test
     * @group empty-or-malformed-tax-info
     * @return array
     * @throws \Exception
     */
    public function it_should_contain_before_tax_amount_when_no_tax_data_is_given(): array
    {
        $amount  = 98.6;
        $payload = array_merge(
            $this->payload,
            [
                'transactionId' => (string) mt_rand(),
                'occurredOn'    => new \DateTimeImmutable(),
                "TAXNAME"       => "",
                "TAXRATE"       => "",
                "AMOUNTPRETAX"  => (string) $amount,
                "AMOUNTTAXONLY" => "",
                "AMOUNTWITHTAX" => (string) $amount
            ]
        );

        $purchaseProcessed = $this->create_purchase_processed($payload, $amount);

        $this->transactionInformation->method('transactionId')->willReturn($payload['transactionId']);
        $this->transactionInformation->method('amount')->willReturn((float) $amount);

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null,
            null,
            $this->transactionInformation
        );

        /** @var $object EmailTemplate */
        $this->assertEquals(
            $payload['AMOUNTPRETAX'],
            $emailTemplate->templateData()['AMOUNTPRETAX']
        );

        return [$emailTemplate, $payload];
    }

    /**
     * @test
     * @group   empty-or-malformed-tax-info
     * @depends it_should_contain_before_tax_amount_when_no_tax_data_is_given
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_contain_same_amount_if_no_tax_is_given(
        array $objectAndPayload
    ): void {

        /** @var $object EmailTemplate */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals(
            $payload['AMOUNTPRETAX'],
            $object->templateData()['AMOUNTWITHTAX']
        );
    }

    /**
     * @param array $taxPayload tax payload
     * @return array
     * @throws \Exception
     */
    private function get_template_data_from_tax_payload(array $taxPayload)
    {
        $amount                         = Base::randomFloat(2);
        $this->payload['transactionId'] = Base::numerify('######');
        $this->payload['occurredOn']    = new \DateTimeImmutable();
        $this->payload['AMOUNTPRETAX']  = Base::numerify('##.#');
        $this->payload['AMOUNTWITHTAX'] = Base::numerify('##.#');
        $this->payload['AMOUNTTAXONLY'] = '';


        $purchaseProcessed = $this->create_purchase_processed($this->payload, $amount, $taxPayload);

        $this->transactionInformation->method('transactionId')->willReturn($this->payload['transactionId']);
        $this->transactionInformation->method('amount')
            ->willReturn((float) $taxPayload['initialAmount']['afterTaxes'] ?? 0);

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null,
            null,
            $this->transactionInformation
        );

        return $emailTemplate->templateData();
    }

    /**
     * @test
     * @group empty-or-malformed-tax-info
     * @throws \Exception
     */
    public function it_should_contain_tax_rate_when_receive_malformed_tax_info()
    {
        $templateData = $this->get_template_data_from_tax_payload(
            [
                'taxName'       => Lorem::word(),
                'taxRate'       => null,
                'initialAmount' => [
                    'beforeTaxes' => Base::numerify('##.#'),
                    'taxes'       => Base::numerify('##.#'),
                    'afterTaxes'  => Base::numerify('##.#'),
                ]
            ]
        );

        $this->assertEquals(self::DEFAULT_TAX_RATE_with_PERCENTAGE, $templateData['TAXRATE']);
    }

    /**
     * @test
     * @group empty-or-malformed-tax-info
     * @throws \Exception
     */
    public function it_should_contain_tax_name_when_receive_malformed_tax_info()
    {
        $templateData = $this->get_template_data_from_tax_payload(
            [
                'taxName'       => null,
                'taxRate'       => Base::numerify('#.#'),
                'initialAmount' => [
                    'beforeTaxes' => Base::numerify('##.#'),
                    'taxes'       => Base::numerify('##.#'),
                    'afterTaxes'  => Base::numerify('##.#'),
                ]
            ]
        );

        $this->assertEquals(EmailTemplateGenericProbiller::DEFAULT_TAX_NAME, $templateData['TAXNAME']);
    }

    /**
     * @test
     */
    public function it_should_return_first6_and_last4_from_transaction_data_if_exists()
    {
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('first6')->willReturn($this->first6);
        $transactionInformation->method('last4')->willReturn($this->last4);

        $retrieveTransactionResult = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->createMock(Site::class),
            $this->createMock(PurchaseProcessed::class),
            $retrieveTransactionResult,
            $this->createMock(MemberInfo::class),
            $this->createMock(PaymentTemplate::class),
           null,
            $transactionInformation
        );

        $reflection = new \ReflectionClass(EmailTemplateGenericProbiller::class);
        $method     = $reflection->getMethod('createMaskedCreditCardForCustomerEmail');
        $method->setAccessible(true);

        $this->assertEquals($this->payload['CARD_NUMBER_XXX'], $method->invoke($emailTemplate));
    }

    /**
     * @test
     */
    public function it_should_return_first6_and_last4_from_payment_template_data_if_exists()
    {
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('first6')->willReturn(null);
        $transactionInformation->method('last4')->willReturn(null);

        $retrieveTransactionResult = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn($this->first6);
        $paymentTemplate->method('lastFour')->willReturn($this->last4);

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->createMock(Site::class),
            $this->createMock(PurchaseProcessed::class),
            $retrieveTransactionResult,
            $this->createMock(MemberInfo::class),
            $paymentTemplate
        );

        $reflection = new \ReflectionClass(EmailTemplateGenericProbiller::class);
        $method     = $reflection->getMethod('createMaskedCreditCardForCustomerEmail');
        $method->setAccessible(true);

        $this->assertEquals($this->payload['CARD_NUMBER_XXX'], $method->invoke($emailTemplate));
    }

    /**
     * @test
     */
    public function it_should_return_first6_and_last4_from_purchaseProcessedEvent_data_if_exists()
    {
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('first6')->willReturn(null);
        $transactionInformation->method('last4')->willReturn(null);

        $retrieveTransactionResult = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn(null);
        $paymentTemplate->method('lastFour')->willReturn(null);

        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed
            ->method('payment')->willReturn(
                [
                    'first6' => $this->first6,
                    'last4'  => $this->last4
                ]
            );

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->createMock(Site::class),
            $purchaseProcessed,
            $retrieveTransactionResult,
            $this->createMock(MemberInfo::class),
            $paymentTemplate
        );

        $reflection = new \ReflectionClass(EmailTemplateGenericProbiller::class);
        $method     = $reflection->getMethod('createMaskedCreditCardForCustomerEmail');
        $method->setAccessible(true);

        $this->assertEquals($this->payload['CARD_NUMBER_XXX'], $method->invoke($emailTemplate));
    }

    /**
     * @test
     */
    public function it_should_return_first6_and_last4_as_XXXXX_if_not_found()
    {
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('first6')->willReturn(null);
        $transactionInformation->method('last4')->willReturn(null);

        $retrieveTransactionResult = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('firstSix')->willReturn(null);
        $paymentTemplate->method('lastFour')->willReturn(null);

        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed->method('payment')->willReturn([]);

        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->createMock(Site::class),
            $purchaseProcessed,
            $retrieveTransactionResult,
            $this->createMock(MemberInfo::class),
            $paymentTemplate
        );

        $reflection = new \ReflectionClass(EmailTemplateGenericProbiller::class);
        $method     = $reflection->getMethod('createMaskedCreditCardForCustomerEmail');
        $method->setAccessible(true);

        $this->assertEquals('XXXXXXXXXXXXXXXX', $method->invoke($emailTemplate));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_have_first6_and_last4_available_and_correct_masking_if_payload_is_correct(): void
    {
        // crosssale info
        $this->addRebillPayload();

        $memberInfo = MemberInfo::create(
            MemberId::create(),
            Email::create('test@mindgeek.com'),
            Username::create('username'),
            $this->payload['REAL_NAME1'],
            $this->payload['REAL_NAME2']
        );

        // sending of cross sale email
        $emailTemplate = EmailTemplateGenericProbiller::getTemplate(
            $this->site,
            $this->purchaseProcessed,
            $this->retrieveTransactionResult,
            $memberInfo,
            null,
            ['initialDays' => 2],
            $this->transactionInformation
        );

        $templateData = $emailTemplate->templateData();

        $this->assertArrayHasKey('CARD_NUMBER_XXX', $templateData);

        $this->assertEquals("123456XXXXXX7788", $templateData['CARD_NUMBER_XXX']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_first6_and_last4_from_purchaseProcessedEvent_data_if_exists_when_paymentTemplate_is_null()
    {
        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);

        $retrieveTransactionResult = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResult->method('transactionInformation')->willReturn($transactionInformation);

        $purchaseProcessed = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessed
            ->method('payment')->willReturn(
                [
                    'first6' => $this->first6,
                    'last4' => $this->last4
                ]
            );

        $emailTemplateGenericProbiller = EmailTemplateGenericProbiller::getTemplate(
            $this->createMock(Site::class),
            $purchaseProcessed,
            $retrieveTransactionResult,
            $this->createMock(MemberInfo::class),
            null
        );

        $reflection = new \ReflectionClass(EmailTemplateGenericProbiller::class);
        $method     = $reflection->getMethod('createMaskedCreditCardForCustomerEmail');
        $method->setAccessible(true);

        $this->assertEquals($this->payload['CARD_NUMBER_XXX'], $method->invoke($emailTemplateGenericProbiller));
    }
}
