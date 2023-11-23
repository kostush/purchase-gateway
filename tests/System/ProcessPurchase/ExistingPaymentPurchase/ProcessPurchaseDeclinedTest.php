<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\ExistingPaymentPurchase;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseDeclinedTest extends ProcessPurchaseBase
{
    /**
     * @var array[]
     */
    protected $selectedCrossSells = [
        [
            'bundleId' => '4475820e-2956-11e9-b210-d663bd873d93',
            'addonId'  => '4e1b0d7e-2956-11e9-b210-d663bd873d93',
            'siteId'   => self::TESTING_SITE_NO_FRAUD,
        ],
    ];

    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_init_for_join_should_contain_x_auth_token(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false);

        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_init_for_join_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_process_for_join_should_return_success(string $token): array
    {
        $processPurchasePayload = $this->processPurchasePayloadWithNoSelectedCrossSale();

        $processPurchasePayload['selectedCrossSells'] = $this->selectedCrossSells;

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPurchasePayload,
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        $initialResponse = json_decode($this->response->getContent(), true);

        // store the cc number to be able to re-use it later for secondary revenue
        $initialResponse['ccNumber'] = $processPurchasePayload['payment']['ccNumber'];

        return $initialResponse;
    }

    /**
     * This test will do a successful join, then using the memberId and subscriptionId for a secondary revenue purchase.
     * On the secondary revenue the transaction will be declined for NSF reasons.
     * @test
     * @depends purchase_process_for_join_should_return_success
     *
     * @param array $initialPurchase Data from Initial purchase.
     *
     * @return array
     * @throws \Exception
     */
    public function nsf_purchase_init_and_process_for_secondary_revenue_should_return_ok(
        array $initialPurchase): array
    {
        $payload                                       = $this->initExistingMemberWithSubscriptionIdPayload();
        $payload['subscriptionId']                     = $initialPurchase['subscriptionId'];
        $payload['memberId']                           = $initialPurchase['memberId'];
        $payload['currency']                           = "USD";
        $payload['amount']                             = 0.02;
        $payload['rebillAmount']                       = 0.02;
        $payload['tax']['initialAmount']['afterTaxes'] = 0.02;
        $payload['tax']['rebillAmount']['afterTaxes']  = 0.02;

        $headers                    = $this->initPurchaseHeaders();
        $headers['X-Force-Cascade'] = 'test-rocketgate';

        // init secondary revenue purchase
        $responseInit = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $headers
        );
        $responseInit->seeHeader('X-Auth-Token');

        $responseInitDecoded = json_decode($this->response->getContent(), true);

        $existingPaymentPurchasePayload = [
            'siteId'  => ProcessPurchaseBase::TESTING_SITE,
            'payment' => [
                'paymentTemplateInformation' => [
                    'lastFour'          => substr($initialPurchase['ccNumber'], -4), // get last4
                    'paymentTemplateId' => $responseInitDecoded['paymentTemplateInfo'][0]['templateId'],
                ],
            ],
        ];

        // process secondary revenue purchase
        $purchaseDeclined = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $existingPaymentPurchasePayload,
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $purchaseDeclined->assertResponseStatus(Response::HTTP_OK);

        return json_decode($purchaseDeclined->response->getContent(), true);
    }

    /**
     * @test
     * @depends nsf_purchase_init_and_process_for_secondary_revenue_should_return_ok
     *
     * @param array $response Purchase Response.
     *
     * @return void
     */
    public function it_should_contain_correct_response_for_NSF_transaction_on_purchase_but_not_nfs_key_with_sec_rev(array $response): void
    {
        $this->assertFalse($response['success']);
        $this->assertEquals(FinishProcess::TYPE, $response['nextAction']['type']);
    }
}