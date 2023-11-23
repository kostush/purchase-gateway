<?php
declare(strict_types=1);

namespace Tests\System\ProcessPurchase\NewPaymentPurchase\NewMember;

use Illuminate\Http\Response;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use Tests\System\InitPurchase\FraudCheckDependingOnSiteConfigurationTest;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

/**
 * @group PurchaseProcess
 */
class ProcessPurchaseTest extends ProcessPurchaseBase
{
    /**
     * @test
     * @return string
     * @throws \Exception
     */
    public function purchase_initiating_should_contain_x_auth_token(): string
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false);
        $response->seeHeader('X-Auth-Token');

        return (string) $this->response->headers->get('X-Auth-Token');
    }


    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function purchase_initiating_should_return_failures_when_using_excessive_initialDays(): void
    {
        $response = $this->initPurchaseProcessWithOneCrossSaleAndExcessiveInitialDays();
        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     *
     * @param string $token Token.
     * @return void
     */
    public function process_purchase_should_return_fail_when_using_member_name_with_number($token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadNameWithNumbers(),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return array
     * @throws \Exception
     */
    public function process_purchase_should_return_success($token): array
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);

        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_session_id(array $response): void
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_key(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_success_with_true_value(array $response): void
    {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_purchaseId_key(array $response): void
    {
        $this->assertArrayHasKey('purchaseId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_memberId_key(array $response): void
    {
        $this->assertArrayHasKey('memberId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_bundleId_key(array $response): void
    {
        $this->assertArrayHasKey('bundleId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_addonId_key(array $response): void
    {
        $this->assertArrayHasKey('addonId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_subscriptionId_key(array $response): void
    {
        $this->assertArrayHasKey('subscriptionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_transactionId_key(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_billerName_key(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_not_contain_errorClassification_key(array $response): void
    {
        $this->assertArrayNotHasKey('errorClassification', $response);
    }

    /**
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_digest_key(array $response): void
    {
        $this->assertArrayHasKey('digest', $response);
    }

    /**
     * When a new member is joining, isUsernamePadded should always be set to false
     *
     * @test
     * @depends process_purchase_should_return_success
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_response_should_contain_isUsernamePadded_set_to_false(array $response): void
    {
        $this->assertArrayHasKey('isUsernamePadded', $response);
        $this->assertFalse($response['isUsernamePadded']);
    }

    /**
     * @test
     * @depends purchase_initiating_should_contain_x_auth_token
     *
     * @param string $token Token.
     *
     * @return void
     * @throws \Exception
     */
    public function second_process_purchase_should_return_session_expired($token): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($token)
        );

        $response->seeJsonContains(['code' => 101]);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function second_process_purchase_should_return_token_expired_with_proper_error_code(): void
    {
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($this->getJwtToken())
        );

        $payload = json_decode($response->response->getContent(), true);

        $this->assertEquals(
            [
                'code'  => Code::TOKEN_EXPIRED,
                'error' => Code::getMessage(Code::TOKEN_EXPIRED)
            ],
            $payload
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function process_purchase_should_return_success_when_using_member_data_with_spaces(): void
    {
        $response = $this->initPurchaseProcessWithOneCrossSale(false);
        $response->seeHeader('X-Auth-Token');
        $token = (string) $this->response->headers->get('X-Auth-Token');

        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithSpaces(),
            $this->processPurchaseHeaders($token)
        );

        $response->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_minimum_user_info()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_missing_tax_information()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_incomplete_tax_information()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function process_purchase_should_return_success_for_missing_site_id_on_cross_sales_tax_information()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_threeDchallenged_null_in_BI_Purchase_Processed_when_threeDRequired_false(): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
            return;
        }

        $payload                      = $this->initPurchasePayload();
        $payload['clientIp']          = $this->faker->ipv4;
        $payload['currency']          = 'USD';
        $payload['clientCountryCode'] = 'US';
        $payload['redirectUrl']       = $this->faker->url;

        $initHeaders              = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        unset($payload['crossSellOptions']);

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload = $this->processPurchasePayloadWithNoSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = $this->faker->creditCardNumber('MasterCard');
        $processPayload['siteId'] = $payload['siteId'];

        // second attempt
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($response->response->getContent(), true);

        $logFile = storage_path('logs/' . env('BI_LOG_FILE'));
        $logContent = exec("cat $logFile | grep ". 'Purchase_Processed');

        $this->assertStringContainsString(sprintf('"sessionId":"%s"', $processResponse['sessionId']), $logContent);
        $this->assertStringContainsString('"threeDchallenged"', $logContent);
        $this->assertStringContainsString('"threeDRequired":false', $logContent);
        $this->assertStringContainsString(sprintf('"threeDchallenged":%s', 'null'), $logContent);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_chargedAmount_for_taxes_in_BI_Purchase_Processed(): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
            return;
        }

        $payload                      = $this->initPurchasePayload();
        $payload['clientIp']          = $this->faker->ipv4;
        $payload['currency']          = 'USD';
        $payload['clientCountryCode'] = 'US';
        $payload['redirectUrl']       = $this->faker->url;

        $initHeaders              = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload = $this->processPurchasePayloadWithOneSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = $this->faker->creditCardNumber('MasterCard');
        $processPayload['siteId'] = $payload['siteId'];

        // second attempt
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($response->response->getContent(), true);

        $logFile = storage_path('logs/' . env('BI_LOG_FILE'));
        $logContent = exec("cat $logFile | grep ". 'Purchase_Processed '." | grep ".$processResponse['sessionId']);

        // Main purchase
        $this->assertStringContainsString(sprintf('"chargedAmountBeforeTaxes":%s',$payload['tax']['initialAmount']['beforeTaxes']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedAmountAfterTaxes":%s',$payload['tax']['initialAmount']['afterTaxes']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedTaxAmount":%s', $payload['tax']['initialAmount']['taxes']), $logContent);

        // Cross sale
        $this->assertStringContainsString(sprintf('"chargedAmountBeforeTaxes":%s',$payload['crossSellOptions'][0]['tax']['initialAmount']['beforeTaxes']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedAmountAfterTaxes":%s',$payload['crossSellOptions'][0]['tax']['initialAmount']['afterTaxes']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedTaxAmount":%s', $payload['crossSellOptions'][0]['tax']['initialAmount']['taxes']), $logContent);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_chargedAmount_for_taxes_in_BI_Purchase_Processed_with_initial_amount_when_tax_not_set(): void
    {
        if (!config('app.feature.common_fraud_enable_for.init.join')) {
            $this->markTestSkipped('Common services fraud not enabled.');
            return;
        }

        $payload                      = $this->initPurchasePayload();
        $payload['clientIp']          = $this->faker->ipv4;
        $payload['currency']          = 'USD';
        $payload['clientCountryCode'] = 'US';
        $payload['redirectUrl']       = $this->faker->url;

        $initHeaders              = $this->initPurchaseHeaders();

        $initHeaders['X-Force-Cascade'] = 'test-rocketgate';

        unset($payload['tax']);
        unset($payload['crossSellOptions'][0]['tax']);

        $initPurchase = $this->json(
            'POST',
            $this->validInitPurchaseRequestUri(),
            $payload,
            $initHeaders
        );

        $processPayload = $this->processPurchasePayloadWithOneSelectedCrossSale();
        $processPayload['payment']['ccNumber'] = $this->faker->creditCardNumber('MasterCard');
        $processPayload['siteId'] = $payload['siteId'];

        // second attempt
        $response = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $processPayload,
            $this->processPurchaseHeaders((string) $initPurchase->response->headers->get('X-Auth-Token'))
        );

        $processResponse = json_decode($response->response->getContent(), true);

        $logFile = storage_path('logs/' . env('BI_LOG_FILE'));
        $logContent = exec("cat $logFile | grep ". 'Purchase_Processed '." | grep ".$processResponse['sessionId']);

        // Main purchase
        $this->assertStringContainsString(sprintf('"chargedAmountBeforeTaxes":%s',$payload['amount']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedAmountAfterTaxes":%s',$payload['amount']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedTaxAmount":%s', null), $logContent);

        // Cross sale
        $this->assertStringContainsString(sprintf('"chargedAmountBeforeTaxes":%s',$payload['crossSellOptions'][0]['amount']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedAmountAfterTaxes":%s',$payload['crossSellOptions'][0]['amount']), $logContent);
        $this->assertStringContainsString(sprintf('"chargedTaxAmount":%s', null), $logContent);
    }
}
