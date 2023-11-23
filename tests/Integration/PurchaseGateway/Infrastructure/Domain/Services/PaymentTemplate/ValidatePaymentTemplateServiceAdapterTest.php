<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProbillerNG\PaymentTemplateServiceClient\Model\AddPaymentTemplatePayloadRocketgatePaymentTemplateBillerFields;
use ProbillerNG\PaymentTemplateServiceClient\Model\ValidatePaymentTemplateResponse;
use ProbillerNG\PaymentTemplateServiceClient\Model\ValidatePaymentTemplateResponsePaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateServiceAdapter;
use Tests\IntegrationTestCase;

class ValidatePaymentTemplateServiceAdapterTest extends IntegrationTestCase
{
    /**
     * @var ValidatePaymentTemplateResponsePaymentTemplate
     */
    protected $validatePaymentTemplateForRocketgate;

    /**
     * @var ValidatePaymentTemplateResponsePaymentTemplate
     */
    protected $validatePaymentTemplateForNetbilling;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validatePaymentTemplateForRocketgate = new ValidatePaymentTemplateResponsePaymentTemplate(
            [
                'firstSix'        => '123456',
                'lastFour'        => '7890',
                'expirationYear'  => '2022',
                'expirationMonth' => '05',
                'lastUsedDate'    => '2019-08-11 15:15:25',
                'createdAt'       => '2019-08-11 15:15:25',
                'billerName'      => 'rocketgate',
                'billerFields'    => [
                    'cardHash'           => 'cardHash',
                    'merchantCustomerId' => $this->faker->uuid
                ]
            ]
        );

        $this->validatePaymentTemplateForNetbilling = new ValidatePaymentTemplateResponsePaymentTemplate(
            [
                'firstSix'        => '123456',
                'lastFour'        => '7890',
                'expirationYear'  => '2022',
                'expirationMonth' => '05',
                'lastUsedDate'    => '2019-08-11 15:15:25',
                'createdAt'       => '2019-08-11 15:15:25',
                'billerName'      => 'netbilling',
                'billerFields'    => [
                    'originId' => '113890225261'
                ]
            ]
        );
    }

    /**
     * @test
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_return_a_rocketgate_payment_template_when_retrieve_payment_template_method_is_called(): void
    {
        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['validatePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('validatePaymentTemplate')->willReturn(
            new ValidatePaymentTemplateResponse(
                [
                    'valid'           => true,
                    'paymentType'     => 'cc',
                    'paymentTemplate' => $this->validatePaymentTemplateForRocketgate
                ]
            )
        );

        $paymentTemplateAdapter = new ValidatePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $result = $paymentTemplateAdapter->validatePaymentTemplate(
            $this->faker->uuid,
            '1234',
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

    /**
     * @test
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_throw_exception_when_client_call_fails(): void
    {
        $this->expectException(RetrievePaymentTemplateException::class);

        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['validatePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('validatePaymentTemplate')
            ->willThrowException(new RetrievePaymentTemplateException());

        $paymentTemplateAdapter = new ValidatePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $paymentTemplateAdapter->validatePaymentTemplate(
            $this->faker->uuid,
            '1234',
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_return_a_netbilling_payment_template_when_retrieve_payment_template_method_is_called(): void
    {
        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['validatePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('validatePaymentTemplate')->willReturn(
            new ValidatePaymentTemplateResponse(
                [
                    'valid'           => true,
                    'paymentType'     => 'cc',
                    'paymentTemplate' => $this->validatePaymentTemplateForNetbilling
                ]
            )
        );

        $paymentTemplateAdapter = new ValidatePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $result = $paymentTemplateAdapter->validatePaymentTemplate(
            $this->faker->uuid,
            '1234',
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

}
