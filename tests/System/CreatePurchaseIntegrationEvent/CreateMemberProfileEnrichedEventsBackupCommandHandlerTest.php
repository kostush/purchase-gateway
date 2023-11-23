<?php
declare(strict_types=1);

namespace Tests\System\CreatePurchaseIntegrationEvent;

use Illuminate\Support\Facades\Config;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseProcessedEnrichedEvent;
use ProBillerNG\PurchaseGateway\Domain\Projector\BundleAddonsProjector;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class CreateMemberProfileEnrichedEventsBackupCommandHandlerTest extends ProcessPurchaseBase
{
    use CreatePurchaseIntegrationEventHelper;

    public const PURCHASE_ENRICHED_EVENT = PurchaseProcessedEnrichedEvent::class;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        Config::set('worker.member-profile-enriched-event.max-execution-cycles', 1);
        Config::set('worker.member-profile-enriched-event.sleep', 0);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_one_integration_events_for_a_purchase_with_one_cross_sale(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithOneCrossSale(false);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithOneSelectedCrossSale(),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        if ($countEvents === 0) {
            $countEvents = $this->countEventsAfterSomeSecsWaitingAsyncProcess($purchaseId);
        }

        $this->assertEquals(1, $countEvents,self::FLAKY_TEST. ' You can check db for the event in StoredEvent.AggregateId:'.$purchaseId);;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_projector_exception_when_ledger_position_wrongfully_updated(): void
    {
        $this->markTestSkipped('TODO: clean up tests - to be removed.');
        $logOverwrite = '/tmp/'. __FUNCTION__;
        Log::getInstance()->pushHandler(new StreamHandler($logOverwrite, Logger::INFO));

        $this->updateLedgerPositionToNow();

        $result = $this->artisan('ng:domain:project', ['projector' => BundleAddonsProjector::WORKER_NAME, 'action' => 'run']);
        $this->assertTrue($result == 0);

        $errormsg = file_get_contents($logOverwrite);
        $this->assertTrue(stristr($errormsg,BundleAddonsProjector::WORKER_NAME . " worker encountered errors.") === false);

        unlink($logOverwrite);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_one_integration_events_for_a_purchase_with_no_selected_cross_sales(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithoutCrossSales(false);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        if ($countEvents === 0) {
            $countEvents = $this->countEventsAfterSomeSecsWaitingAsyncProcess($purchaseId);
        }

        $this->assertEquals(1, $countEvents,self::FLAKY_TEST. ' You can check db for the event in StoredEvent.AggregateId:'.$purchaseId);;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_one_integration_events_for_a_purchase_with_two_cross_sales(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithTwoCrossSales(true);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithTwoSelectedCrossSale(),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        if ($countEvents === 0) {
            $countEvents = $this->countEventsAfterSomeSecsWaitingAsyncProcess($purchaseId);
        }

        $this->assertEquals(1, $countEvents,self::FLAKY_TEST. ' You can check db for the event in StoredEvent.AggregateId:'.$purchaseId);;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_one_integration_events_for_a_purchase_with_one_cross_sale_and_no_rebill_data(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithOneCrossSaleWithoutRebill(true);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithOneSelectedCrossSale(),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        if ($countEvents === 0) {
            $countEvents = $this->countEventsAfterSomeSecsWaitingAsyncProcess($purchaseId);
        }

        $this->assertEquals(1, $countEvents,self::FLAKY_TEST. ' You can check db for the event in StoredEvent.AggregateId:'.$purchaseId);;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_one_integration_events_for_a_purchase_with_one_cross_sale_and_no_tax_data(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithOneCrossSaleWithoutTax(true);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithOneSelectedCrossSale(),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        if ($countEvents === 0) {
            $countEvents = $this->countEventsAfterSomeSecsWaitingAsyncProcess($purchaseId);
        }

        $this->assertEquals(1, $countEvents,self::FLAKY_TEST. ' You can check db for the event in StoredEvent.AggregateId:'.$purchaseId);;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_generate_one_integration_events_for_a_purchase_without_cross_sale_and_minimum_member_data(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithoutCrossSales(false);

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithMinimumMemberPayload(),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        if ($countEvents === 0) {
            $countEvents = $this->countEventsAfterSomeSecsWaitingAsyncProcess($purchaseId);
        }

        $this->assertEquals(1, $countEvents,self::FLAKY_TEST. ' You can check db for the event in StoredEvent.AggregateId:'.$purchaseId);;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_generate_any_integration_events_for_an_aborted_purchase(): void
    {
        $this->updateLedgerPositionToNow();

        $initResponse = $this->initPurchaseProcessWithOneCrossSale(
            false,
            ProcessPurchaseBase::REALITY_KINGS_SITE_ID
        );

        // Attempt 1 - only one attempt is made because there is only one biller in the cascade,
        // therefore only one submit can be made for one biller
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithInvalidCreditCard(ProcessPurchaseBase::REALITY_KINGS_SITE_ID),
            $this->processPurchaseHeaders($this->retrieveTokenFromInitResponse($initResponse))
        );

        $purchaseId = $this->retrievePurchaseIdFromProcessResponse($response);

        $this->artisan('ng:worker', ['worker' => 'member-profile-enriched-event', 'action' => 'run']);

        $countEvents = $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);

        $this->assertEquals(0, $countEvents,self::FLAKY_TEST);
    }

    /**
     * @param string $purchaseId
     * @return int
     * @throws \Exception
     */
    private function countEventsAfterSomeSecsWaitingAsyncProcess(string $purchaseId): int
    {
        $extraTimeToFinishAsyncProcess = 1;
        sleep($extraTimeToFinishAsyncProcess);
        return $this->countStoredEvent($purchaseId, self::PURCHASE_ENRICHED_EVENT);
    }
}
