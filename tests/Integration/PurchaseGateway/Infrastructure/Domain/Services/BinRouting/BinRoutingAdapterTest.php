<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProbillerNG\BinRoutingServiceClient\Model\RoutingCodeItem;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\NetbillingBinRoutingAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\RocketgateBinRoutingAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\BinRoutingClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\BinRoutingTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeTypeException;
use Tests\IntegrationTestCase;

class BinRoutingAdapterTest extends IntegrationTestCase
{

    /**
     * @var string
     */
    protected $netbillingBin;

    /**
     * @var string
     */
    protected $siteTag;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->netbillingBin = '463316';
        $this->siteTag       = '028C';
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeApiException
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_rocketgate_bin_routing_collection_object(): void
    {
        $binRoutingClientMock = $this->getMockBuilder(BinRoutingClient::class)
            ->setMethods(['retrieveRocketgateBinCard'])
            ->disableOriginalConstructor()
            ->getMock();
        $binRoutingClientMock->method('retrieveRocketgateBinCard')->willReturn(
            new RoutingCodeItem(
                [
                    'attempt' => 1,
                    'routingCode' => 123123,
                    'bankName'  => 'FirstBank'
                ]
            )
        );

        $rocketgateBinRoutingAdapter = new RocketgateBinRoutingAdapter(
            $binRoutingClientMock,
            new BinRoutingTranslator()
        );

        $result = $rocketgateBinRoutingAdapter->retrieve(
            substr($this->faker->creditCardNumber, 0, 6),
            (string) $this->faker->numberBetween(1, 200000),
            $this->faker->currencyCode,
            $this->faker->numberBetween(1, 3),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(BinRoutingCollection::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeApiException
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_netbilling_bin_routing_collection_object(): void
    {
        $binRoutingClientMock = $this->getMockBuilder(BinRoutingClient::class)
            ->setMethods(['retrieveNetbillingBinCard'])
            ->disableOriginalConstructor()
            ->getMock();

        $binRoutingClientMock->method('retrieveNetbillingBinCard')->willReturn(
            new RoutingCodeItem(
                [
                    'attempt' => 1,
                    'routingCode' => "1",
                    'bankName'  => 'bank4'
                ]
            )
        );

        $netbillingBinRoutingAdapter = new NetbillingBinRoutingAdapter(
            $binRoutingClientMock,
            new BinRoutingTranslator()
        );

        $result = $netbillingBinRoutingAdapter->retrieve(
            $this->netbillingBin,
            (string) $this->faker->numberBetween(1, 200000),
            $this->siteTag,
            $this->faker->numberBetween(1, 3),
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(BinRoutingCollection::class, $result);
    }
}
