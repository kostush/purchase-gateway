<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProbillerNG\PaymentTemplateServiceClient\Model\AddPaymentTemplatePayloadRocketgatePaymentTemplateBillerFields;
use ProbillerNG\PaymentTemplateServiceClient\Model\InlineResponse404;
use ProbillerNG\PaymentTemplateServiceClient\Model\InlineResponse500;
use ProbillerNG\PaymentTemplateServiceClient\Model\PaymentTemplate as ClientPaymentTemplate;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponse;
use ProbillerNG\PaymentTemplateServiceClient\Model\RetrieveResponsePaymentTemplate;
use ProbillerNG\PaymentTemplateServiceClient\Model\ValidatePaymentTemplateResponse;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslator;
use Tests\UnitTestCase;

class PaymentTemplateTranslatorTest extends UnitTestCase
{
    /**
     * string
     */
    const UUID_1 = '19805311-7dde-3767-b7b3-e5849d970310';

    /**
     * string
     */
    const UUID_2 = 'd7d56ba9-8167-30ab-b891-7da150ce5787';

    /**
     * @var string
     */
    protected $uuid1;

    /**
     * @var string
     */
    protected $uuid2;

    /**
     * @var PaymentTemplateTranslator
     */
    private $translator;

    /**
     * @var RetrieveResponsePaymentTemplate
     */
    protected $retrievePaymentTemplateForRocketgate;

    /**
     * @var RetrieveResponsePaymentTemplate
     */
    protected $retrievePaymentTemplateForNetbilling;

    /**
     * setup function
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->uuid1 = self::UUID_1;
        $this->uuid2 = self::UUID_2;

        $this->retrievePaymentTemplateForRocketgate = new RetrieveResponsePaymentTemplate(
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

        $this->retrievePaymentTemplateForNetbilling = new RetrieveResponsePaymentTemplate(
            [
                'firstSix'        => '123456',
                'lastFour'        => '7890',
                'expirationYear'  => '2022',
                'expirationMonth' => '05',
                'lastUsedDate'    => '2019-08-11 15:15:25',
                'createdAt'       => '2019-08-11 15:15:25',
                'billerName'      => 'netbilling',
                'billerFields'    => [
                    'originId'   => '113890225261',
                    'binRouting' => 'INT/PX#100XTxEP'
                ]
            ]
        );

        $this->translator = new PaymentTemplateTranslator();
    }

    /**
     * @test
     * @return void
     * @throws PaymentTemplateCodeErrorException
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_for_internal_server_error_result(): void
    {
        $this->expectException(PaymentTemplateCodeErrorException::class);

        $result = new InlineResponse500(
            [
                'code'  => 1,
                'error' => 'Internal server error'
            ]
        );

        $this->translator->translateRetrieveAllPaymentTemplatesForMember($result);
    }

    /**
     * @test
     * @return void
     * @throws PaymentTemplateCodeErrorException
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_for_not_being_client_payment_template_object(): void
    {
        $this->expectException(PaymentTemplateCodeTypeException::class);

        $result = [new \stdClass()];

        $this->translator->translateRetrieveAllPaymentTemplatesForMember($result);
    }

    /**
     * @test
     * @return PaymentTemplateCollection
     * @throws PaymentTemplateCodeErrorException
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_payment_template_collection_object(): PaymentTemplateCollection
    {
        $routingCodeItems = [
            new ClientPaymentTemplate(
                [
                    'templateId'      => $this->uuid1,
                    'firstSix'        => '123456',
                    'isExpired'       => false,
                    'expirationYear'  => '2022',
                    'expirationMonth' => '05',
                    'lastUsedDate'    => '2019-08-11 15:15:25',
                    'createdAt'       => '2019-08-11 15:15:25',
                ]
            ),
            new ClientPaymentTemplate(
                [
                    'templateId'      => $this->uuid2,
                    'firstSix'        => '987654',
                    'isExpired'       => true,
                    'expirationYear'  => date('Y') + 1,
                    'expirationMonth' => '06',
                    'lastUsedDate'    => '2019-02-12 15:15:25',
                    'createdAt'       => '2019-02-12 15:15:25',
                ]
            )
        ];

        $result = $this->translator->translateRetrieveAllPaymentTemplatesForMember($routingCodeItems);

        $this->assertInstanceOf(PaymentTemplateCollection::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_collection_object
     * @param PaymentTemplateCollection $result The previous result
     * @return void
     */
    public function the_returned_collection_should_be_indexed_by_the_first_template_id(PaymentTemplateCollection $result
    ): void {
        $this->assertTrue($result->offsetExists(self::UUID_1));
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_collection_object
     * @param PaymentTemplateCollection $result The previous result
     * @return void
     */
    public function the_returned_collection_should_be_indexed_by_the_second_template_id(
        PaymentTemplateCollection $result
    ): void {
        $this->assertTrue($result->offsetExists(self::UUID_2));
    }

    /**
     * @test
     * @return void
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_for_not_being_retrieve_response_object(): void
    {
        $this->expectException(PaymentTemplateCodeTypeException::class);

        $result = [new \stdClass()];

        $this->translator->translateRetrievePaymentTemplate($this->faker->uuid, $result);
    }

    /**
     * @test
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    public function it_should_return_a_rocketgate_payment_template_when_translated_with_retrieve_payment_template(): void
    {
        $response = new RetrieveResponse(
            [
                'paymentType'     => 'cc',
                'paymentTemplate' => $this->retrievePaymentTemplateForRocketgate
            ]
        );

        $result = $this->translator->translateRetrievePaymentTemplate($this->faker->uuid, $response);

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     */
    public function it_should_throw_exception_for_not_being_validate_payment_template_response_object(): void
    {
        $this->expectException(PaymentTemplateCodeTypeException::class);

        $result = [new \stdClass()];

        $this->translator->translateValidatePaymentTemplate($this->faker->uuid, $result);
    }

    /**
     * @test
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateDataNotFoundException
     * @return void
     */
    public function it_should_throw_exception_for_being_inline_response_404_response_object(): void
    {
        $this->expectException(PaymentTemplateDataNotFoundException::class);

        $result = new InlineResponse404(
            [
                'error' => $this->faker->word,
                'code'  => $this->faker->randomDigitNotNull
            ]
        );

        $this->translator->translateValidatePaymentTemplate($this->faker->uuid, $result);
    }

    /**
     * @test
     * @return void
     * @throws PaymentTemplateCodeTypeException
     * @throws PaymentTemplateDataNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_rocketgate_payment_template_when_translated_with_validate(): void
    {
        $response = new ValidatePaymentTemplateResponse(
            [
                'valid'           => true,
                'paymentType'     => 'cc',
                'paymentTemplate' => $this->retrievePaymentTemplateForRocketgate
            ]
        );

        $result = $this->translator->translateValidatePaymentTemplate($this->faker->uuid, $response);

        $this->assertInstanceOf(PaymentTemplate::class, $result);
    }

    /**
     * @test
     * @throws PaymentTemplateCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @return PaymentTemplate
     */
    public function it_should_return_a_netbilling_payment_template_when_translated_with_retrieve_payment_template(): PaymentTemplate
    {
        $response = new RetrieveResponse(
            [
                'paymentType'     => 'cc',
                'paymentTemplate' => $this->retrievePaymentTemplateForNetbilling
            ]
        );

        $result = $this->translator->translateRetrievePaymentTemplate($this->faker->uuid, $response);

        $this->assertInstanceOf(PaymentTemplate::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_netbilling_payment_template_when_translated_with_retrieve_payment_template
     * @param PaymentTemplate $result The translated payment template
     * @return void
     */
    public function it_should_have_bin_routing_code_in_payment_template_for_netbilling(PaymentTemplate $result): void
    {
        $this->assertEquals($result->billerFields()['binRouting'], $this->retrievePaymentTemplateForNetbilling['billerFields']['binRouting']);
    }

    /**
     * @test
     * @throws PaymentTemplateCodeTypeException
     * @throws PaymentTemplateDataNotFoundException
     * @throws \ProBillerNG\Logger\Exception
     * @return PaymentTemplate
     */
    public function it_should_return_a_netbilling_payment_template_when_translated_with_validate(): PaymentTemplate
    {
        $response = new ValidatePaymentTemplateResponse(
            [
                'valid'           => true,
                'paymentType'     => 'cc',
                'paymentTemplate' => $this->retrievePaymentTemplateForNetbilling
            ]
        );

        $result = $this->translator->translateValidatePaymentTemplate($this->faker->uuid, $response);
        $this->assertInstanceOf(PaymentTemplate::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_netbilling_payment_template_when_translated_with_validate
     * @param PaymentTemplate $result The translated payment template
     * @return void
     */
    public function it_should_have_bin_routing_code_in_payment_template_for_netbilling_when_translated_with_validate(PaymentTemplate $result): void
    {
        $this->assertEquals($result->billerFields()['binRouting'], $this->retrievePaymentTemplateForNetbilling['billerFields']['binRouting']);
    }
}
