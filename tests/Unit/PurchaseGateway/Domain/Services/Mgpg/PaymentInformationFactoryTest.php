<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\Mgpg;

use ProbillerMGPG\Common\PaymentMethod;
use ProbillerMGPG\Common\PaymentType;
use ProbillerMGPG\Purchase\Process\PaymentInformation\CardInformation;
use ProbillerMGPG\Purchase\Process\PaymentInformation\CheckInformation;
use ProbillerMGPG\Purchase\Process\PaymentInformation\CryptoInformation;
use ProbillerMGPG\Purchase\Process\PaymentInformation\PaymentTemplateInformation;
use ProbillerMGPG\Purchase\Process\PaymentInformation\User;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\NgResponseService;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\PaymentInformationFactory;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\ProcessPurchaseRequest as MgpgProcessPurchaseRequest;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest;
use Tests\UnitTestCase;

class PaymentInformationFactoryTest extends UnitTestCase
{
    const CRYPTOCURRENCY_COINPAYMENT_LITECOIN = 'LTCT';

    /**
     * @var PaymentInformationFactory
     */
    private $paymentInformationFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentInformationFactory = new PaymentInformationFactory(new NgResponseService());
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_card_information_when_payment_is_card(): void
    {
        $request = $this->createMock(ProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('payment')
            ->willReturn(['cardInformation' => []]);

        $this->assertInstanceOf(CardInformation::class, $this->paymentInformationFactory->create($request));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_check_information_when_payment_is_check(): void
    {
        $request = $this->createMock(ProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('payment')
            ->willReturn(['checkInformation' => []]);
        $request->expects($this->any())
            ->method('accountNumber')
            ->willReturn('12345678');
        $request->expects($this->any())
            ->method('routingNumber')
            ->willReturn('1234');
        $request->expects($this->any())
            ->method('socialSecurityLast4')
            ->willReturn('1234');
        $request->expects($this->any())
            ->method('savingAccount')
            ->willReturn(false);
        $request->expects($this->any())
            ->method('label')
            ->willReturn('label');
        $this->assertInstanceOf(CheckInformation::class, $this->paymentInformationFactory->create($request));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_payment_tempalte_information_when_payment_is_payment_template(): array
    {
        $request = $this->createMock(ProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('payment')
            ->willReturn(
                [
                    'method'                     => ChequePaymentInfo::PAYMENT_METHOD,
                    'paymentTemplateInformation' => [
                        'accountNumberLast4' => '9999',
                        'paymentTemplateId'  => '0d3ef6aa-d31b-41e4-a67e-b422bad0bbad'
                    ]
                ]
            );

        $request->expects($this->any())
            ->method('paymentMethod')
            ->willReturn(ChequePaymentInfo::PAYMENT_METHOD);

        $paymentInformation = $this->paymentInformationFactory->create($request);
        $this->assertInstanceOf(PaymentTemplateInformation::class, $paymentInformation);

        return $paymentInformation->toArray();
    }

    /**
     * @test
     * @depends it_should_return_payment_tempalte_information_when_payment_is_payment_template
     *
     * @param array $paymentInformation
     *
     * @return array
     */
    public function it_should_return_payment_tempalte_information_with_validation_parameters(array $paymentInformation
    ): array {
        $this->assertArrayHasKey('validationParameters', $paymentInformation['paymentTemplateInformation']);

        return $paymentInformation['paymentTemplateInformation']['validationParameters'];
    }

    /**
     * @test
     * @depends it_should_return_payment_tempalte_information_with_validation_parameters
     *
     * @param array $validationParameters
     */
    public function it_should_return_validation_parameter_for_check_payment_template(array $validationParameters): void
    {
        $this->assertArrayHasKey(PaymentTemplate::CHEQUE_IDENTITY_VERIFICATION_METHOD, $validationParameters);
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_user_when_payment_is_hybrid(): void
    {
        $request = $this->createMock(ProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('paymentType')
            ->willReturn('banktransfer');
        $request->expects($this->any())
            ->method('paymentMethod')
            ->willReturn('zelle');
        $this->assertInstanceOf(User::class, $this->paymentInformationFactory->create($request));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_credit_card_as_default_payment_when_no_payment_information_is_informed(): void
    {
        $request = $this->createMock(ProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('payment')
            ->willReturn([]);
        $this->assertInstanceOf(CardInformation::class, $this->paymentInformationFactory->create($request));
    }

    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_user_payment_in_case_of_gift_card_payment_method_and_type(): void
    {
        $request = $this->createMock(ProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('paymentType')
            ->willReturn(PaymentType::GIFTCARDS);
        $request->expects($this->any())
            ->method('paymentMethod')
            ->willReturn(PaymentMethod::GIFTCARDS);
        $this->assertInstanceOf(User::class, $this->paymentInformationFactory->create($request));
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_user_when_payment_is_crypto(): void
    {
        $request = $this->createMock(MgpgProcessPurchaseRequest::class);
        $request->expects($this->any())
            ->method('paymentType')
            ->willReturn(PaymentType::CRYPTOCURRENCY );
        $request->expects($this->any())
            ->method('paymentMethod')
            ->willReturn(PaymentMethod::CRYPTOCURRENCY);
        $request->expects($this->any())
            ->method('cryptoCurrency')
            ->willReturn(self::CRYPTOCURRENCY_COINPAYMENT_LITECOIN);
        $this->assertInstanceOf(CryptoInformation::class, $this->paymentInformationFactory->create($request));
    }
}
