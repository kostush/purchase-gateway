<?php

namespace PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Projection\Domain\Exceptions\TransientException;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventBase;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\TransientConfigServiceException;
use Tests\IntegrationTestCase;

class CreateMemberProfileEnrichedEventBaseTest extends IntegrationTestCase
{
    /**
     * @test
     */
    public function it_should_throws_a_transient_exception_when_config_service_fails(): void
    {
        /**
         * Expecting the TransientException
         */
        $this->expectException(TransientConfigServiceException::class);

        /**
         * Creating a implementation to use the CreateMemberProfileEnrichedEventBase trait methods
         */
        $createMemberProfileEnrichedEventImplementation = new class(){

            use CreateMemberProfileEnrichedEventBase;

            public function publicInit($transactionService,
                                       $bundleRepository,
                                       $serviceBusFactory,
                                       $configService){

                $this->init($transactionService,
                            $bundleRepository,
                            $serviceBusFactory,
                            $configService);
            }

            public function publicRetrieveSite(string $siteId){
              $this->retrieveSite($siteId);
            }

            public function handleEnrichedEvent($purchaseProcessedEnrichedEvent): void
            {
                // TODO: Implement handleEnrichedEvent() method.
            }
        };

        /**
         * Mocking CreateMemberProfileEnrichedEventBase dependencies
         */
        $transactionService = $this->createMock(TransactionService::class);
        $bundleRepository   = $this->createMock(BundleRepositoryReadOnly::class);
        $serviceBusFactory  = $this->createMock(ServiceBusFactory::class);
        $configService      = $this->createMock(ConfigService::class);

        /**
         * Making the configService to fail
         */
        $configService->method('getSite')->willThrowException(new \Exception());

        $createMemberProfileEnrichedEventImplementation->publicInit($transactionService,
                                                                 $bundleRepository,
                                                                 $serviceBusFactory,
                                                                 $configService);

        $createMemberProfileEnrichedEventImplementation->publicRetrieveSite($this->faker->uuid);
    }



    /**
     * @test
     */
    public function it_should_throws_a_transient_exception_when_config_service_returns_null(): void
    {
        /**
         * Expecting the TransientException
         */
        $this->expectException(TransientConfigServiceException::class);

        /**
         * Creating a implementation to use the CreateMemberProfileEnrichedEventBase trait methods
         */
        $createMemberProfileEnrichedEventImplementation = new class(){

            use CreateMemberProfileEnrichedEventBase;

            public function publicInit($transactionService,
                                       $bundleRepository,
                                       $serviceBusFactory,
                                       $configService){

                $this->init($transactionService,
                            $bundleRepository,
                            $serviceBusFactory,
                            $configService);
            }

            public function publicRetrieveSite(string $siteId){
                $this->retrieveSite($siteId);
            }

            public function handleEnrichedEvent($purchaseProcessedEnrichedEvent): void
            {
                // TODO: Implement handleEnrichedEvent() method.
            }
        };

        /**
         * Mocking CreateMemberProfileEnrichedEventBase dependencies
         */
        $transactionService = $this->createMock(TransactionService::class);
        $bundleRepository   = $this->createMock(BundleRepositoryReadOnly::class);
        $serviceBusFactory  = $this->createMock(ServiceBusFactory::class);
        $configService      = $this->createMock(ConfigService::class);

        /**
         * Making the configService return null
         */
        $configService->method('getSite')->willReturn(null);

        $createMemberProfileEnrichedEventImplementation->publicInit($transactionService,
                                                                    $bundleRepository,
                                                                    $serviceBusFactory,
                                                                    $configService);

        $createMemberProfileEnrichedEventImplementation->publicRetrieveSite($this->faker->uuid);
    }
}