<?php
declare(strict_types=1);

namespace PurchaseGateway\Application\DTO\Purchase\Process;

use PHPUnit\Framework\MockObject\MockObject;
use ProbillerMGPG\Purchase\Init\Response\CryptoSettings;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Init\PurchaseInitCommandHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandResult;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use Tests\UnitTestCase;

class PurchaseInitCommandHttpDTOTest extends UnitTestCase
{
    /**
     * @test
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    public function it_should_return_crypto_settings_when_purchase_result_has_crypto_settings_response(): void
    {

        $sessionId            = $this->faker->uuid;

        $cryptoSettings       = new CryptoSettings();
        $cryptoSettings->name = 'Litecoin Testnet';
        $cryptoCode           = 'LTCT';
        $cryptoSettings->code = $cryptoCode;
        $cryptoSettings->icon = 'https://mgpg-assets-dev.web.app/LTCT.svg';

        $arrayOfCryptoSettings[] = $cryptoSettings;

        /** @var MockObject|PurchaseInitCommandResult $mockedPurchaseInitCommandResult */
        $mockedPurchaseInitCommandResult = $this->createMock(PurchaseInitCommandResult::class);
        $mockedPurchaseInitCommandResult->method('cryptoSettings')->willReturn($arrayOfCryptoSettings);
        $mockedPurchaseInitCommandResult->method('sessionId')->willReturn($sessionId);

        $purchaseInitDto = new PurchaseInitCommandHttpDTO($mockedPurchaseInitCommandResult);

        $this->assertArrayHasKey('cryptoSettings', $purchaseInitDto->jsonSerialize());
        $this->assertEquals($cryptoCode, ($purchaseInitDto->jsonSerialize()['cryptoSettings'][0])->code);
        $this->assertEquals($sessionId, $purchaseInitDto->jsonSerialize()['sessionId']);
    }

    /**
     * @test
     * @throws Exception
     * @throws UnknownBillerNameException
     */
    public function it_should_not_return_crypto_settings_when_purchase_result_does_not_have_crypto_settings_response(
    ): void
    {
        $sessionId = $this->faker->uuid;
        /** @var MockObject|PurchaseInitCommandResult $mockedPurchaseInitCommandResult */
        $mockedPurchaseInitCommandResult = $this->createMock(PurchaseInitCommandResult::class);
        $mockedPurchaseInitCommandResult->method('cryptoSettings')->willReturn(null);
        $mockedPurchaseInitCommandResult->method('sessionId')->willReturn($sessionId);

        $purchaseInitDto = new PurchaseInitCommandHttpDTO($mockedPurchaseInitCommandResult);

        $this->assertArrayNotHasKey('cryptoSettings', $purchaseInitDto->jsonSerialize());
        $this->assertEquals($sessionId, $purchaseInitDto->jsonSerialize()['sessionId']);
    }
}
