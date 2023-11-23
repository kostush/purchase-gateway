<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NuData;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataCard;
use Tests\UnitTestCase;

class NuDataCardTest extends UnitTestCase
{
    /**
     * @var NuDataCard
     */
    private $nuDataCard;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nuDataCard = $this->createNuDataCard();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_holder_name(): void
    {
        $this->assertEquals('Mister Axe', $this->nuDataCard->holderName());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_card_number(): void
    {
        $this->assertEquals($_ENV['ROCKETGATE_COMMON_CARD_NUMBER'], $this->nuDataCard->cardNumber());
    }
}
