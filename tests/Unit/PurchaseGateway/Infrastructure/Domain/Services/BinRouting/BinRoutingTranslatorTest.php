<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProbillerNG\BinRoutingServiceClient\Model\BadRequestError;
use ProbillerNG\BinRoutingServiceClient\Model\Error;
use ProbillerNG\BinRoutingServiceClient\Model\MethodNotAllowedResponse;
use ProbillerNG\BinRoutingServiceClient\Model\RoutingCodeItem;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\BinRoutingTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions\BinRoutingCodeTypeException;
use Tests\UnitTestCase;

class BinRoutingTranslatorTest extends UnitTestCase
{
    /**
     * @var BinRoutingTranslator
     */
    private $translator;

    /**
     * setup function
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->translator = new BinRoutingTranslator();
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_for_error_result(): void
    {
        $this->expectException(BinRoutingCodeErrorException::class);

        $routingCodeEror = new Error(
            [
                'code'    => 1,
                'message' => 'error'
            ]
        );

        $this->translator->translate($routingCodeEror, $this->faker->uuid);
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_for_bad_request_result(): void
    {
        $this->expectException(BinRoutingCodeErrorException::class);

        $routingCodeBadRequestEror = new BadRequestError(
            [
                'code'    => 1,
                'message' => 'error'
            ]
        );

        $this->translator->translate($routingCodeBadRequestEror, $this->faker->uuid);
    }

    /**
     * @test
     * @return void
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_for_method_not_allowed_result(): void
    {
        $this->expectException(BinRoutingCodeErrorException::class);

        $routingCodeBadRequestEror = new MethodNotAllowedResponse(
            [
                'code'    => 1,
                'message' => 'error'
            ]
        );

        $this->translator->translate($routingCodeBadRequestEror, $this->faker->uuid);
    }

    /**
     * @test
     * @return array
     * @throws BinRoutingCodeErrorException
     * @throws BinRoutingCodeTypeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_bin_routing_collection_object(): array
    {
        $routingCodeItems = [
            new RoutingCodeItem(
                [
                    'attempt'     => 1,
                    'routingCode' => '123123',
                    'bankName'    => 'FirstBank'
                ]
            ),
            new RoutingCodeItem(
                [
                    'attempt'     => 2,
                    'routingCode' => '456456',
                    'bankName'    => 'SecondBank'
                ]
            )
        ];

        $uuid   = $this->faker->uuid;
        $result = $this->translator->translate($routingCodeItems, $uuid);

        $this->assertInstanceOf(BinRoutingCollection::class, $result);

        return [$result, $uuid];
    }

    /**
     * @test
     * @depends it_should_return_a_bin_routing_collection_object
     * @param array $result The previous result
     * @return void
     */
    public function the_returned_collection_should_be_indexed_by_item_id_and_attempt_number(array $result): void
    {
        list($collection, $itemId) = $result;
        $this->assertArrayHasKey($itemId . '_' . 1, $collection);
        $this->assertArrayHasKey($itemId . '_' . 2, $collection);
    }
}
