<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NewPaymentProcessCommandHandler\PurchaseProcessCommandHandler;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ItemCouldNotBeRestoredException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use ReflectionClass;
use ReflectionException;
use Tests\UnitTestCase;
use Throwable;

class CheckUserInputTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws ReflectionException|UnknownBillerNameException
     */
    public function it_should_check_user_input_from_base(): void
    {
        $fraudResponse = FraudAdvice::create();
        $fraudResponse->markBlacklistedOnProcess();

        $command = $this->createProcessCommand();

        $fraudService = $this->createMock(FraudService::class);
        $fraudService->method('retrieveAdvice')->willReturn($fraudResponse);

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $fraudService,
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $this->createMock(SessionHandler::class),
                    $this->createMock(PurchaseService::class),
                    $this->createMock(ProcessPurchaseDTOAssembler::class),
                    $this->createMock(SiteRepositoryReadOnly::class),
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->setMethods(null)
            ->getMock();

        $reflection = new ReflectionClass(NewPaymentProcessCommandHandler::class);

        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchaseProcessAttribute = $reflection->getProperty('purchaseProcess');
        $purchaseProcessAttribute->setAccessible(true);
        $purchaseProcessAttribute->setValue($handler, $purchaseProcess);

        $method = $reflection->getMethod('checkUserInput');
        $method->setAccessible(true);
        $method->invoke(
            $handler,
            $this->createMock(Email::class),
            $this->createMock(Bin::class),
            $this->createMock(Zip::class),
            $this->createMock(SiteId::class)
        );

        $this->assertTrue($purchaseProcessAttribute->getValue($handler)->isBlacklistedOnProcess());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_build_check_user_input(): void
    {
        if (config('app.feature.common_fraud_enable_for.process.new_credit_card')) {
            $this->markTestSkipped('This method has not been used on new fraud service');
            return;
        }

        $fraudResponse = FraudAdvice::create();
        $fraudResponse->markBlacklistedOnProcess();

        $command = $this->createProcessCommand();

        $fraudService = $this->createMock(FraudService::class);
        $fraudService->method('retrieveAdvice')->willReturn($fraudResponse);

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $fraudService,
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $this->createMock(SessionHandler::class),
                    $this->createMock(PurchaseService::class),
                    $this->createMock(ProcessPurchaseDTOAssembler::class),
                    $this->createMock(SiteRepositoryReadOnly::class),
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->setMethods(null)
            ->getMock();

        $reflection = new ReflectionClass(NewPaymentProcessCommandHandler::class);

        $purchaseProcess = PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true));

        $purchaseProcessAttribute = $reflection->getProperty('purchaseProcess');
        $purchaseProcessAttribute->setAccessible(true);
        $purchaseProcessAttribute->setValue($handler, $purchaseProcess);

        $method = $reflection->getMethod('buildCheckUserInput');
        $method->setAccessible(true);
        $method->invoke(
            $handler,
            $command
        );

        $this->assertTrue($purchaseProcessAttribute->getValue($handler)->isBlacklistedOnProcess());
    }
}
