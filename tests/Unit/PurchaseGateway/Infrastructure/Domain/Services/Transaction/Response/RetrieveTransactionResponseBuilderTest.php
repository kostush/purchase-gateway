<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\InvalidResponseException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResponseBuilder;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use Tests\UnitTestCase;

class RetrieveTransactionResponseBuilderTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws InvalidResponseException
     * @throws Exception
     * @throws UnknownBillerIdException
     */
    public function it_should_throw_an_invalid_response_exception_when_response_cannot_be_mapped(): void
    {
        $transactionResponse = $this->createMock(RetrieveTransaction::class);
        $transactionResponse->method('getPaymentType')->willReturn('needs work!');
        $this->expectException(InvalidResponseException::class);
        RetrieveTransactionResponseBuilder::build($transactionResponse, false);
    }
}
