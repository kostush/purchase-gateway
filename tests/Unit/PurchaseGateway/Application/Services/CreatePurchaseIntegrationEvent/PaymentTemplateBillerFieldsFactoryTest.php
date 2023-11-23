<?php

namespace PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\PaymentTemplateBillerFieldsFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use Tests\UnitTestCase;

class PaymentTemplateBillerFieldsFactoryTest extends UnitTestCase
{
    /**
     * @test
     * @throws \Exception
     */
    public function it_should_return_merchant_id_into_biller_fields_when_a_rocketgate_is_passed()
    {
        // Building needed data to call  PaymentTemplateBillerFieldsFactory
        $retrieveTransactionTransaction = [
            'transactionId' => $this->faker->uuid,
            'first6'        => '123456',
            'last4'         => '4321',
            'amount'        => '10',
            'createdAt'     => $this->faker->dateTime()->format('Y-m-d H:i:s'),
            'rebillAmount'  => '0',
            'status'        => ''
        ];

        $trasaction = new RetrieveTransactionTransaction($retrieveTransactionTransaction);

        $data = [
            'billerId'           => '23423',
            'billerName'         => 'rocketgate',
            'merchantId'         => '123456789',
            'merchantPassword'   => $this->faker->password,
            'customerId'         => $this->faker->uuid,
            'transactionId'      => $this->faker->uuid,
            'currency'           => 'USD',
            'siteId'             => $this->faker->uuid,
            'paymentType'        => 'cc',
            'transaction'        => $trasaction,
            'billerTransactions' => []
        ];

        $response                 = new RetrieveTransaction($data);
        $memberInformation        = new MemberInformation($response);
        $CCTransactionInformation = new NewCCTransactionInformation($response);

        $rocketgateBillerFields = RocketgateBillerFields::create(
            $data['merchantId'],
            $this->faker->uuid,
            $data['siteId'],
            $this->faker->word,
            false,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $retrieveTransactionResult = new RocketgateCCRetrieveTransactionResult(
            $response,
            $memberInformation,
            $CCTransactionInformation,
            $rocketgateBillerFields
        );

        // Calling  PaymentTemplateBillerFieldsFactory
        $paymentTemplateFields = PaymentTemplateBillerFieldsFactory::create($retrieveTransactionResult);

        // Assertions
        $this->assertArrayHasKey('merchantId', $paymentTemplateFields);
    }
}