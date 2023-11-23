<?php

namespace Tests\System\ProcessPurchase\ExistingPaymentPurchase;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class ProcessPurchaseWithSubscriptionIdTest extends ProcessPurchaseBase
{
    /**
     * @var array
     */
    protected $existingPaymentPurchasePayload = [
        'siteId'  => ProcessPurchaseBase::TESTING_SITE,
        'payment' => [
            'paymentTemplateInformation' => [
                'lastFour'          => '7770',
                'paymentTemplateId' => '0b69cc99-4447-4614-a7f0-dc65ab109ef2'
            ]
        ]
    ];

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
    public function purchase_initiating_should_contain_x_auth_token(): string
    {
        $response = $this->initExistingMemberWithSubscriptionId([], true);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     * @param string $token Token
     * @return string
     */
    public function process_purchase_should_return_bad_request_when_template_id_not_found_in_init(string $token): string
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->existingPaymentPurchasePayload,
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($response->response->getContent())->error;
    }

    /**
     * @test
     * @depends process_purchase_should_return_bad_request_when_template_id_not_found_in_init
     * @param string $message Message
     * @return void
     */
    public function process_purchase_should_return_right_message_when_invalid_last_four(string $message): void
    {
        $this->assertEquals('Invalid Payment Template Id', $message);
    }

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
     * @test
     * @depends purchase_process_for_join_should_return_success
     *
     * @param array $initialPurchase Data from Initial purchase.
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_init_and_process_for_secondary_revenue_should_return_ok_with_subscriptionId_and_memberId_provided(
        array $initialPurchase): array
    {
        $payload                                       = $this->initExistingMemberWithSubscriptionIdPayload();
        $payload['subscriptionId']                     = $initialPurchase['subscriptionId'];
        $payload['memberId']                           = $initialPurchase['memberId'];
        $payload['currency']                           = "USD";
        $payload['amount']                             = 5.99;
        $payload['rebillAmount']                       = 4.99;
        $payload['tax']['initialAmount']['afterTaxes'] = 5.99;
        $payload['tax']['rebillAmount']['afterTaxes']  = 4.99;

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
        $purchaseSuccess = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $existingPaymentPurchasePayload,
            $this->processPurchaseHeaders((string) $this->response->headers->get('X-Auth-Token'))
        );

        $purchaseSuccess->assertResponseStatus(Response::HTTP_OK);

        $decodedResponse = json_decode($purchaseSuccess->response->getContent(), true);

        $this->assertEquals($initialPurchase['memberId'], $decodedResponse['memberId']);
        $this->assertEquals($initialPurchase['subscriptionId'], $decodedResponse['subscriptionId']);

        return $decodedResponse;
    }

    /**
     * @test
     * @depends purchase_init_and_process_for_secondary_revenue_should_return_ok_with_subscriptionId_and_memberId_provided
     *
     * @param array $response Purchase Response.
     *
     * @return void
     */
    public function it_should_contain_correct_response_for_sec_rev_transaction(array $response): void
    {
        $this->assertTrue($response['success']);
        $this->assertEquals(FinishProcess::TYPE, $response['nextAction']['type']);
    }

    /**
     * When an existing member purchases with payment template, isUsernamePadded should always be set to false
     * as the user cannot change the subscription's username.
     *
     * @test
     * @depends purchase_init_and_process_for_secondary_revenue_should_return_ok_with_subscriptionId_and_memberId_provided
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_isUsernamePadded_set_to_false(array $response): void
    {
        $this->assertArrayHasKey('isUsernamePadded', $response);
        $this->assertFalse($response['isUsernamePadded']);
    }
}
