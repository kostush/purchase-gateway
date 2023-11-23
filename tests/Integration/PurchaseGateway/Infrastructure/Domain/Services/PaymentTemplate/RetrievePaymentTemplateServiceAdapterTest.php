<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProbillerNG\PaymentTemplateServiceClient\Model\AddPaymentTemplatePayloadRocketgatePaymentTemplateBillerFields;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponse;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponsePaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\BasePaymentTemplateAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslator;
use Tests\IntegrationTestCase;

class RetrievePaymentTemplateServiceAdapterTest extends IntegrationTestCase
{
    /**
     * @var RetrieveResponsePaymentTemplate
     */
    protected $retrievePaymentTemplateForRocketgate;

    /**
     * @var RetrieveResponsePaymentTemplate
     */
    protected $retrievePaymentTemplateForNetbilling;
    /**
     * @var \ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponse
     */
    private $retrievePaymentTemplateForNetbillingWithoutOriginId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retrievePaymentTemplateForRocketgate = new RetrieveResponse(
            [
                'paymentType'     => 'cc',
                'paymentTemplate' => new RetrieveResponsePaymentTemplate(
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
                ),
            ]
        );

        $this->retrievePaymentTemplateForNetbilling = new RetrieveResponse(
            [
                'paymentType'     => 'cc',
                'paymentTemplate' => new RetrieveResponsePaymentTemplate(
                    [
                        'firstSix'        => '123456',
                        'lastFour'        => '7890',
                        'expirationYear'  => '2022',
                        'expirationMonth' => '05',
                        'lastUsedDate'    => '2019-08-11 15:15:25',
                        'createdAt'       => '2019-08-11 15:15:25',
                        'billerName'      => 'netbilling',
                        'billerFields'    => [
                            'originId' => '113890225261',
                            'binRouting' => 'INT\/PX#100XTxEP'
                        ]
                    ]
                ),
            ]
        );

        $this->retrievePaymentTemplateForNetbillingWithoutOriginId = new RetrieveResponse(
            [
                'paymentType'     => 'cc',
                'paymentTemplate' => new RetrieveResponsePaymentTemplate(
                    [
                        'firstSix'        => '123456',
                        'lastFour'        => '7890',
                        'expirationYear'  => '2022',
                        'expirationMonth' => '05',
                        'lastUsedDate'    => '2019-08-11 15:15:25',
                        'createdAt'       => '2019-08-11 15:15:25',
                        'billerName'      => 'netbilling',
                        'billerFields'    => [
                            'cardHash' => 'cardHash',
                            'binRouting' => 'INT\/PX#100XTxEP'
                        ]
                    ]
                ),
            ]
        );
    }

    /**
     * @test
     * @return void
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_return_a_rocketgate_payment_template_when_retrieve_payment_template_method_is_called(): void
    {
        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['retrievePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('retrievePaymentTemplate')->willReturn(
            $this->retrievePaymentTemplateForRocketgate
        );

        $paymentTemplateAdapter = new RetrievePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $result = $paymentTemplateAdapter->retrievePaymentTemplate(
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_throw_exception_when_client_call_fails(): void
    {
        $this->expectException(RetrievePaymentTemplateException::class);

        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['retrievePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('retrievePaymentTemplate')->willThrowException(new RetrievePaymentTemplateException());

        $paymentTemplateAdapter = new RetrievePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $paymentTemplateAdapter->retrievePaymentTemplate(
            $this->faker->uuid,
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_return_a_netbilling_payment_template_when_retrieve_payment_template_method_is_called(): void
    {
        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['retrievePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('retrievePaymentTemplate')->willReturn(
            $this->retrievePaymentTemplateForNetbilling
        );

        $paymentTemplateAdapter = new RetrievePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $result = $paymentTemplateAdapter->retrievePaymentTemplate(
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws RetrievePaymentTemplateException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_return_a_netbilling_payment_template_when_retrieve_payment_template_without_origin_id(): void
    {
        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['retrievePaymentTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('retrievePaymentTemplate')->willReturn(
            $this->retrievePaymentTemplateForNetbillingWithoutOriginId
        );

        $paymentTemplateAdapter = new RetrievePaymentTemplateServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $result = $paymentTemplateAdapter->retrievePaymentTemplate(
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }
}
