<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseFraudHttpDTO;
use ProBillerNG\PurchaseGateway\Application\FraudIntegrationMapper;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\JsonWebToken;
use Tests\UnitTestCase;

class ProcessPurchaseFraudHttpDTOTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_the_correct_dto_structure()
    {
        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterface = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterface);

        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markBlacklistedOnProcess();

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $initializedItem = $this->createMock(InitializedItem::class);
        $site            = $this->createSite();
        $initializedItem->method('siteId')->willReturn($site->siteId());
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseProcess->method('isBlacklistedOnProcess')->willReturn(true);
        $purchaseProcess->method('fraudAdvice')->willReturn($fraudAdvice);

        $fraudRecommendationCollection = new FraudRecommendationCollection([
            FraudIntegrationMapper::mapFraudAdviceToFraudRecommendation($fraudAdvice)
        ]);

        $purchaseProcess->method('fraudRecommendationCollection')->willReturn(
            $fraudRecommendationCollection
        );

        $purchaseProcess->method('fraudRecommendation')->willReturn($fraudRecommendationCollection->first());

        $expected = [
            'sessionId' => '',
            'fraudAdvice'                   => [
                'blacklist' => true,
                'captcha'   => false,
            ],
            'fraudRecommendation'           => [
                'severity' => FraudIntegrationMapper::BLOCK,
                'code'     => FraudRecommendation::BLACKLIST,
                'message'  => FraudIntegrationMapper::BLACKLIST_REQUIRED
            ],
            'fraudRecommendationCollection' => [
                [
                    'severity' => FraudIntegrationMapper::BLOCK,
                    'code'     => FraudRecommendation::BLACKLIST,
                    'message'  => FraudIntegrationMapper::BLACKLIST_REQUIRED
                ]
            ],
            'nextAction'                    => ['type' => 'restartProcess'],
            'digest'                        => ''
        ];

        $processPurchaseFraudHttpDTO = new ProcessPurchaseFraudHttpDTO($purchaseProcess, $tokenGenerator, $site);
        $this->assertEquals($expected, $processPurchaseFraudHttpDTO->jsonSerialize());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_first_recommendation_from_collection_when_there_is_a_recommednatio_collection(): void
    {

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterace  = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterace);

        $site = $this->createSite();

        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markBlacklistedOnProcess();

        $purchaseProcess = $this->createProcess();

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $firstSeverity = 'severity';
        $firstCode     = 000;

        $fraudRecommendationCollectionArray = [
            0 => $this->createFraudCollectionArrayNode($firstCode,$firstSeverity),
            1 => $this->createFraudCollectionArrayNode(300,'block'),
            2 => $this->createFraudCollectionArrayNode(1000,'allow'),
        ];

        $fraudRecommendationCollection = FraudRecommendationCollection::createFromArray($fraudRecommendationCollectionArray);

        $purchaseProcess->setFraudRecommendationCollection($fraudRecommendationCollection);

        $processPurchaseFraudHttpDTO = new ProcessPurchaseFraudHttpDTO($purchaseProcess, $tokenGenerator, $site);

        $this->assertEquals($firstCode, $processPurchaseFraudHttpDTO->jsonSerialize()['fraudRecommendation']['code']);
        $this->assertEquals($firstSeverity, $processPurchaseFraudHttpDTO->jsonSerialize()['fraudRecommendation']['severity']);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_mapped_fraud_recommendation_when_there_is_no_recommendation_collection(): void
    {

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenInterace  = $this->createMock(JsonWebToken::class);
        $tokenGenerator->method('generateWithPublicKey')->willReturn($tokenInterace);

        $site = $this->createSite();

        $fraudAdvice = FraudAdvice::create();
        $fraudAdvice->markBlacklistedOnProcess();

        $purchaseProcess = $this->createProcess();

        $purchaseProcess->setFraudAdvice($fraudAdvice);

        $processPurchaseFraudHttpDTO = new ProcessPurchaseFraudHttpDTO($purchaseProcess, $tokenGenerator, $site);

        $this->assertEquals(FraudRecommendation::BLACKLIST, $processPurchaseFraudHttpDTO->jsonSerialize()['fraudRecommendation']['code']);
        $this->assertEquals(FraudIntegrationMapper::BLOCK, $processPurchaseFraudHttpDTO->jsonSerialize()['fraudRecommendation']['severity']);
    }

    /**
     * @param int    $code
     * @param string $severity
     * @return array
     */
    private function createFraudCollectionArrayNode(int $code, string $severity): array
    {
        return [
            'code'     => $code,
            'severity' => $severity,
            'message'  => 'message'
        ];
    }

    /**
     * @return PurchaseProcess
     * @throws Exception
     */
    private function createProcess(): PurchaseProcess
    {
        return PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            $this->faker->numberBetween(1000, 9999),
            $this->createMock(UserInfo::class),
            $this->createMock(PaymentInfo::class),
            new InitializedItemCollection(),
            $this->faker->uuid,
            $this->faker->uuid,
            $this->createMock(CurrencyCode::class),
            null,
            null,
            null
        );
    }
}
