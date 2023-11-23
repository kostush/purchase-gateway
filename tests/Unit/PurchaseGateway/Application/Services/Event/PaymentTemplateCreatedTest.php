<?php
declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Application\Services\Event\PaymentTemplateCreatedEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use Tests\UnitTestCase;

class PaymentTemplateCreatedTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $rocketgateBillerFields;

    /**
     * @var array
     */
    protected $netbillingBillerFields;

    /**
     * @var array
     */
    protected $epochBillerFields;


    protected function setUp(): void
    {
        parent::setUp();

        $this->rocketgateBillerFields = [
            'cardHash' => $this->faker->firstName,
            'merchantCustomerId' => $this->faker->uuid
        ];

        $this->netbillingBillerFields = [
            'originId' => base64_encode("CS:113832223893".(string) $this->faker->randomNumber(4))
        ];

        $this->epochBillerFields = [
            'memberId' => '1206684681'
        ];
    }

    /**
     * @test
     * @throws \Exception
     * @return PaymentTemplateCreatedEvent
     */
    public function it_should_return_a_payment_template_created_event_with_rocketgate(): PaymentTemplateCreatedEvent
    {
        $event = new PaymentTemplateCreatedEvent(
            $this->faker->uuid,
            CCPaymentInfo::PAYMENT_TYPE,
            '123456',
            '1234',
            2025,
            12,
            new \DateTimeImmutable(),
            RocketgateBiller::BILLER_NAME,
            $this->faker->uuid,
            $this->rocketgateBillerFields
        );

        $this->assertInstanceOf(PaymentTemplateCreatedEvent::class, $event);
        return $event;
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_rocketgate
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_should_contain_all_keys_when_to_array_method_is_called_with_rocketgate(PaymentTemplateCreatedEvent $event)
    {
        $toArrayResult = $event->toArray();
        $keys = [
            'type',
            'memberId',
            'paymentType',
            'firstSix',
            'lastFour',
            'expirationYear',
            'expirationMonth',
            'billerName',
            'billerFields',
            'createdAt',
            'message_type'
        ];

        $this->assertCount(0, array_diff($keys, array_keys($toArrayResult)));
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_rocketgate
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_should_return_array_when_to_array_method_is_called_with_rocketgate(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $this->assertGreaterThan(0, count($toArrayResult));
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_rocketgate
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_should_contain_biler_fields_key_when_to_array_method_is_called_with_rocketgate(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $this->assertArrayHasKey('billerFields', $toArrayResult);
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_rocketgate
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_contain_rocketgate_card_hash_under_biller_fields_keys_when_to_array_method_is_called(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $billerFields = $toArrayResult['billerFields'];

        $this->assertArrayHasKey('cardHash', $billerFields);
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_rocketgate
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_contain_rocketgate_merchant_customer_id_under_biller_fields_keys_when_to_array_method_is_called(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $billerFields = $toArrayResult['billerFields'];

        $this->assertArrayHasKey('merchantCustomerId', $billerFields);
    }

    /**
     * @test
     * @throws \Exception
     * @return PaymentTemplateCreatedEvent
     */
    public function it_should_return_a_payment_template_created_event_with_netbilling(): PaymentTemplateCreatedEvent
    {
        $event = new PaymentTemplateCreatedEvent(
            $this->faker->uuid,
            CCPaymentInfo::PAYMENT_TYPE,
            '123456',
            '1234',
            2025,
            12,
            new \DateTimeImmutable(),
            NetbillingBiller::BILLER_NAME,
            $this->faker->uuid,
            $this->netbillingBillerFields
        );

        $this->assertInstanceOf(PaymentTemplateCreatedEvent::class, $event);
        return $event;
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_netbilling
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_should_contain_all_keys_when_to_array_method_is_called_with_netbilling(PaymentTemplateCreatedEvent $event)
    {
        $toArrayResult = $event->toArray();
        $keys = [
            'type',
            'memberId',
            'paymentType',
            'firstSix',
            'lastFour',
            'expirationYear',
            'expirationMonth',
            'billerName',
            'billerFields',
            'createdAt',
            'message_type'
        ];

        $this->assertCount(0, array_diff($keys, array_keys($toArrayResult)));
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_netbilling
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function event_should_return_biller_fields_when_to_array_method_is_called_with_netbilling(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $billerFields = $toArrayResult['billerFields'];

        $this->assertIsArray($billerFields);
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_netbilling
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function it_should_contain_netbilling_fields_under_biller_fields_keys_when_to_array_method_is_called(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $billerFields = $toArrayResult['billerFields'];

        $this->assertArrayHasKey('originId', $billerFields);
    }

    /**
     * @test
     * @throws \Exception
     * @return PaymentTemplateCreatedEvent
     */
    public function it_should_return_a_payment_template_created_event_with_epoch(): PaymentTemplateCreatedEvent
    {
        $event = new PaymentTemplateCreatedEvent(
            $this->faker->uuid,
            CCPaymentInfo::PAYMENT_TYPE,
            null,
            null,
            null,
            null,
            new \DateTimeImmutable(),
            EpochBiller::BILLER_NAME,
            $this->faker->uuid,
            $this->epochBillerFields
        );

        $this->assertInstanceOf(PaymentTemplateCreatedEvent::class, $event);
        return $event;
    }

    /**
     * @test
     * @depends it_should_return_a_payment_template_created_event_with_epoch
     * @param PaymentTemplateCreatedEvent $event PaymentTemplateCreatedEvent
     * @return void
     */
    public function it_should_contain_member_id_under_biller_fields_keys_when_to_array_method_is_called(PaymentTemplateCreatedEvent $event): void
    {
        $toArrayResult = $event->toArray();

        $billerFields = $toArrayResult['billerFields'];

        $this->assertArrayHasKey('memberId', $billerFields);
    }
}
