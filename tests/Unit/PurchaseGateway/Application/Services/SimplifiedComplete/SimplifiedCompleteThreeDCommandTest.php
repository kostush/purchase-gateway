<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\SimplifiedComplete;

use Exception;
use ProBillerNG\PurchaseGateway\Application\Services\SimplifiedComplete\SimplifiedCompleteThreeDCommand;
use Tests\UnitTestCase;

class SimplifiedCompleteThreeDCommandTest extends UnitTestCase
{
    /*
     * @var SimplifiedCompleteThreeDCommand
     */
    private $command;

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        parse_str(
            'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&invoiceID=aba9b991-61642f82223498.08058272&hash=4qEW12Qdl5%2FYxkCtRbZ%2FHT%2Bi1NM%3D',
            $queryString
        );

        $this->command = new SimplifiedCompleteThreeDCommand(
            $this->faker->uuid,
            [
                'flag'      => '17c6f59e222',
                'id'        => '64d98d86-61642f822233e7.53329385',
                'invoiceID' => 'aba9b991-61642f82223498.08058272',
                'hash'      => '4qEW12Qdl5/YxkCtRbZ/HT+i1NM=',
            ]
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_url_encoded_string(): void
    {
        self::assertIsString($this->command->queryString());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_non_empty_string_as_query_string(): void
    {
        self::assertGreaterThan(0, strlen($this->command->queryString()));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_non_empty_string_as_invoice_id(): void
    {
        self::assertGreaterThan(0, strlen($this->command->invoiceId()));
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_non_empty_string_as_hash(): void
    {
        self::assertGreaterThan(0, strlen($this->command->hash()));
    }
}
