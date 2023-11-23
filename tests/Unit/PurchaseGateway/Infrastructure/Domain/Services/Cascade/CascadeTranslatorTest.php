<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Cascade;

use ProbillerNG\CascadeServiceClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeTranslator;
use Tests\UnitTestCase;

class CascadeTranslatorTest extends UnitTestCase
{
    /**
     * @var Cascade
     */
    private $cascade;

    /**
     * @var CascadeTranslator
     */
    private $translator;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->translator = new CascadeTranslator();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_cascade_when_translator_receives_correct_data_from_in_memory_adapter(): void
    {
        $this->cascade = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]));

        $this->assertInstanceOf(Cascade::class, $this->translator->translate($this->cascade));

        $this->assertEquals($this->cascade, $this->translator->translate($this->cascade));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_cascade_when_translator_receives_correct_data_from_cascade_service(): void
    {
        $cascade = new InlineResponse200(
            [
                'billers' => [
                    RocketgateBiller::BILLER_NAME,
                    NetbillingBiller::BILLER_NAME
                ]
            ]
        );

        $result = $this->translator->translateCascade($cascade);

        $this->assertInstanceOf(Cascade::class, $result);
    }

    /**
     * @test
     * @return Cascade
     */
    public function it_should_return_cascade_with_default_biller_when_translator_receives_correct_data(): Cascade
    {
        $cascade = new \stdClass();

        $result = $this->translator->translateCascade($cascade);

        $this->assertInstanceOf(Cascade::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_cascade_with_default_biller_when_translator_receives_correct_data
     * @param Cascade $cascade Cascade
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     */
    public function it_should_have_rocketgate_as_default_biller(Cascade $cascade): void
    {
        $this->assertSame((new RocketgateBiller())->name(), $cascade->nextBiller()->name());
    }
}
