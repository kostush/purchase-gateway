<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate;

use ProbillerNG\PaymentTemplateServiceClient\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslator;
use Tests\IntegrationTestCase;

class RetrievePaymentTemplatesServiceAdapterTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeApiException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeErrorException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\PaymentTemplateCodeTypeException
     */
    public function it_should_return_a_payment_template_collection_object(): void
    {
        $paymentTemplateClientMock = $this->getMockBuilder(PaymentTemplateClient::class)
            ->setMethods(['retrieveAllPaymentTemplatesForMember'])
            ->disableOriginalConstructor()
            ->getMock();

        $paymentTemplateClientMock->method('retrieveAllPaymentTemplatesForMember')->willReturn(
            [
                new PaymentTemplate(
                    [
                        'templateId'      => $this->faker->uuid,
                        'firstSix'        => '123456',
                        'isExpired'       => false,
                        'expirationYear'  => '2022',
                        'expirationMonth' => '05',
                        'lastUsedDate'    => '2019-08-11 15:15:25',
                        'createdAt'       => '2019-08-11 15:15:25',
                    ]
                )
            ]
        );

        $paymentTemplateAdapter = new RetrievePaymentTemplatesServiceAdapter(
            $paymentTemplateClientMock,
            new PaymentTemplateTranslator()
        );

        $result = $paymentTemplateAdapter->retrieveAllPaymentTemplates(
            $this->faker->uuid,
            'cc',
            $this->faker->uuid
        );

        $this->assertInstanceOf(PaymentTemplateCollection::class, $result);
    }
}
