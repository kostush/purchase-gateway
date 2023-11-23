<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use CommonServices\FraudServiceClient\Model\FraudResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\Exceptions\FraudAdviceCsCodeTypeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudAdviceCs\FraudAdviceCsTranslator;
use Tests\UnitTestCase;

/**
 * @deprecated
 * Class FraudAdviceCsTranslatorTest
 * @package Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice
 */
class FraudAdviceCsTranslatorTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws FraudAdviceCsCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_translation_exception_if_response_is_not_correct_type()
    {
        $this->expectException(FraudAdviceCsCodeTypeException::class);
        $translator = new FraudAdviceCsTranslator();

        $translator->translate($this->createMock(PaymentTemplateCollection::class), []);
    }

    /**
     * @test
     * @return void
     * @throws FraudAdviceCsCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_update_safe_bin_flag_from_payment_template_inside_collection()
    {
        $translator = new FraudAdviceCsTranslator();
        $collection = $this->createMock(PaymentTemplateCollection::class);
        $collection->method('toArray')->willReturn(
            [
                [
                    'templateId' => 1,
                    'firstSix'   => '123456'
                ]
            ]
        );

        $template = $this->createMock(PaymentTemplate::class);
        $template->expects($this->once())->method('setIsSafe')->with(true);

        $collection->method('get')->willReturn($template);

        $adviceResponseDto = $this->createMock(FraudResponseDto::class);
        $adviceResponseDto->method('getSafebin')->willReturn(['123456' => true]);

        $translator->translate($collection, $adviceResponseDto);
    }
}
