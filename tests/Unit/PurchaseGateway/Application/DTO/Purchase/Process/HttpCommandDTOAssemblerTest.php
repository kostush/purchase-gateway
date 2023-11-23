<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseFraudHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\BlockedDueToFraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processed;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use Tests\UnitTestCase;

class HttpCommandDTOAssemblerTest extends UnitTestCase
{
    /**
     * @var HttpCommandDTOAssembler
     */
    private $httpCommandDTOAssembler;

    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $jsonTokenMok   = $this->createMock(JsonWebToken::class);
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $cryptInterface = $this->createMock(CryptService::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($jsonTokenMok);
        $tokenGenerator->method('generateWithGenericKey')->willReturn($jsonTokenMok);
        $site = $this->createSite();

        $this->httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, $cryptInterface);

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem       = $this->createMock(InitializedItem::class);
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $this->purchaseProcess->method('sessionId')->willReturn(SessionId::create());
    }

    /**
     * @test
     * @return void
     * @throws IllegalStateTransitionException
     */
    public function is_should_return_process_purchase_general_http_DTO_when_is_fraud_is_false()
    {
        $this->purchaseProcess->method('isFraud')->willReturn(false);
        $this->purchaseProcess->method('state')->willReturn(Processed::create());

        $this->assertInstanceOf(
            ProcessPurchaseGeneralHttpDTO::class,
            $this->httpCommandDTOAssembler->assemble($this->purchaseProcess)
        );
    }

    /**
     * @test
     * @return void
     */
    public function is_should_return_process_purchase_fraud_http_DTO_when_is_fraud_is_true()
    {
        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isBlacklistedOnProcess')->willReturn(true);
        $this->purchaseProcess->method('fraudAdvice')->willReturn($fraudAdvice);

        $this->purchaseProcess->method('isFraud')->willReturn(true);
        $this->purchaseProcess->method('state')->willReturn(BlockedDueToFraudAdvice::create());

        $this->assertInstanceOf(
            ProcessPurchaseFraudHttpDTO::class,
            $this->httpCommandDTOAssembler->assemble($this->purchaseProcess)
        );
    }
}
