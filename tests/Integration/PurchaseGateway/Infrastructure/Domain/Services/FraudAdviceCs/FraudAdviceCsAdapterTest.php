<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs;

use CommonServices\FraudServiceClient\Model\FraudResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeTypeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsTranslator;
use Tests\IntegrationTestCase;

/**
 * @deprecated
 * Class FraudAdviceCsAdapterTest
 * @package Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs
 */
class FraudAdviceCsAdapterTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws FraudAdviceCsCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeApiException
     */
    public function it_should_update_safe_bin_flag_from_payment_template_collection()
    {
        $translator = new FraudAdviceCsTranslator();
        $collection = new PaymentTemplateCollection();
        $collection->offsetSet(
            1,
            PaymentTemplate::create(
                '1',
                '123456',
                '1234',
                '2019',
                '11',
                '2019-08-11 15:15:25',
                '2019-08-11 15:15:25',
                RocketgateBiller::BILLER_NAME,
                []
            )
        );

        $adviceResponseDto = $this->createMock(FraudResponseDto::class);
        $adviceResponseDto->method('getSafebin')->willReturn(['123456' => true]);

        $client = $this->createMock(FraudAdviceCsClient::class);
        $client->method('retrieve')->willReturn($adviceResponseDto);

        $adaptor = new FraudAdviceCsAdapter($client, $translator);
        $adaptor->retrieveAdvice($collection, $this->faker->uuid);

        $this->assertTrue($collection->get('1')->isSafe());
    }
}
