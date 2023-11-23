<?php

namespace Tests\System\ProcessPurchase\ExistingPaymentPurchase;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\FinishProcess;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseWithoutSubscriptionIdTest extends ProcessPurchaseBase
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
    public function purchase_initiating_without_subscription_id_should_contain_x_auth_token(): string
    {
        $response = $this->initExistingMemberWithoutSubscriptionId();
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @test
     * @depends purchase_initiating_without_subscription_id_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function process_purchase_should_return_error_when_username_or_password_not_in_process_payload($token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processExistingMemberPurchasePayload(),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
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
     * This test will do a successful join, then using the memberId and entrySiteId for a secondary revenue purchase.
     * @test
     * @depends purchase_process_for_join_should_return_success
     *
     * @param array $initialPurchase Data from Initial purchase.
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_init_and_process_for_secondary_revenue_should_return_ok_with_entrySiteId_and_memberId_provided(
        array $initialPurchase
    ): array {
        $payload                                       = $this->initExistingMemberWithoutSubscriptionIdPayload();
        $payload['memberId']                           = $initialPurchase['memberId'];
        $payload['entrySiteId']                        = ProcessPurchaseBase::TESTING_SITE;
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

        $username = 'testPurchase' . random_int(100, 999);

        $existingPaymentPurchasePayload = [
            'siteId'  => ProcessPurchaseBase::TESTING_SITE,
            'payment' => [
                'paymentTemplateInformation' => [
                    'lastFour'          => substr($initialPurchase['ccNumber'], -4), // get last4
                    'paymentTemplateId' => $responseInitDecoded['paymentTemplateInfo'][0]['templateId'],
                ],
            ],
            'member'  => [
                'email'       => $username . '@test.mindgeek.com',
                'username'    => $username,
                'password'    => 'test12345',
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => 'CA',
                'zipCode'     => 'h1h1h1',
                'address1'    => '123 Random Street',
                'address2'    => 'Hello Boulevard',
                'city'        => 'Montreal',
                'state'       => 'CA',
                'phone'       => '514-000-0911',
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

        return $decodedResponse;
    }

    /**
     * @test
     * @depends purchase_init_and_process_for_secondary_revenue_should_return_ok_with_entrySiteId_and_memberId_provided
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
     * When we use the entrySiteId a new subscription is always created therefore the username should be a new one
     * hence isUsernamePadded set to false.
     *
     * @test
     * @depends purchase_init_and_process_for_secondary_revenue_should_return_ok_with_entrySiteId_and_memberId_provided
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
