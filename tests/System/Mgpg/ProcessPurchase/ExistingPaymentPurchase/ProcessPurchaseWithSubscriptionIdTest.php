<?php

namespace Tests\System\Mgpg\ProcessPurchase\ExistingPaymentPurchase;

use Illuminate\Http\Response;
use Tests\System\Mgpg\ProcessPurchase\ProcessPurchaseBase;

class ProcessPurchaseWithSubscriptionIdTest extends ProcessPurchaseBase
{
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
            $this->buildProcessPayloadWithNonexistentPaymentTemplateOnInit(),
            $this->processPurchaseHeaders($token)
        );
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($response->response->getContent())->error;
    }

    /**
     * @return array
     */
    private function buildProcessPayloadWithNonexistentPaymentTemplateOnInit(): array
    {
        $randomUuid = '4821b06d-1f99-481a-845b-853283d650c2';

        return [
            'siteId'  => ProcessPurchaseBase::TESTING_SITE,
            'member'  => [
                "username"  => $this->faker->userName,
                "email"     => $this->faker->email,
                "password"  => "test1234",
                "firstName" => $this->faker->userName,
                "lastName"  => $this->faker->lastName,
            ],
            'payment' => [
                'paymentTemplateInformation' => [
                    'lastFour'          => '5765',
                    'paymentTemplateId' => $randomUuid
                ]
            ]
        ];
    }
}
