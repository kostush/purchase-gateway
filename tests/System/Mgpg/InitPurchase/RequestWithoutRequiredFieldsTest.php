<?php

namespace Tests\System\Mgpg\InitPurchase;

use Illuminate\Http\Response;

/**
 * @group InitPurchase
 */
class RequestWithoutRequiredFieldsTest extends InitPurchase
{
    /**
     * @param string $field Field Name.
     *
     * @return string
     */
    private function error_message(string $field): string
    {
        return 'The given data was invalid. The ' . $field . ' field is required.';
    }

    /**
     * @param array $payload Payload.
     *
     * @return array
     */
    private function purchase_initiating_failing(array $payload): array
    {
        $this->json('POST', $this->validRequestUri(), $payload, $this->header());
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
        return json_decode($this->response->getContent(), true);
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_bundle_id(): array
    {
        $payload_no_bundle = $this->payload;
        unset($payload_no_bundle['bundleId']);

        return $this->purchase_initiating_failing($payload_no_bundle);
    }

    /**
     * @test
     * @depends purchase_initiating_fail_without_bundle_id
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function purchase_initiating_fail_message_without_bundle_id(array $response): void
    {
        $this->assertEquals($response['error'], $this->error_message("bundle id"));
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_addon_id(): array
    {
        $payload_no_addon = $this->payload;
        unset($payload_no_addon['addonId']);

        return $this->purchase_initiating_failing($payload_no_addon);
    }

    /**
     * @test
     * @depends purchase_initiating_fail_without_addon_id
     *
     * @param array $response Response Result.
     *
     * @return void
     */
    public function purchase_initiating_fail_message_without_addon_id(array $response): void
    {
        $this->assertEquals($response['error'], $this->error_message("addon id"));
    }


    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_amount(): array
    {
        $payload_no_amount = $this->payload;
        unset($payload_no_amount['amount']);

        return $this->purchase_initiating_failing($payload_no_amount);
    }

    /**
     * @test
     * @depends purchase_initiating_fail_without_amount
     * @param array $response Response Result.
     *
     * @return void
     */
    public function purchase_initiating_fail_message_without_amount(array $response): void
    {
        $this->assertEquals($response['error'], $this->error_message("amount"));
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_initial_days(): array
    {
        $payload_no_initial_days = $this->payload;
        unset($payload_no_initial_days['initialDays']);

        return $this->purchase_initiating_failing($payload_no_initial_days);
    }

    /**
     * @test
     * @depends purchase_initiating_fail_without_initial_days
     * @param array $response Response Result.
     *
     * @return void
     */
    public function purchase_initiating_fail_message_without_initial_days(array $response): void
    {
        $this->assertEquals($response['error'], $this->error_message("initial days"));
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_client_ip(): array
    {
        $payload_no_ip = $this->payload;
        unset($payload_no_ip['clientIp']);
        return $this->purchase_initiating_failing($payload_no_ip);
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_client_country_code(): array
    {
        $payloadWithoutClientCountryCode = $this->payload;
        unset($payloadWithoutClientCountryCode['clientCountryCode']);
        return $this->purchase_initiating_failing($payloadWithoutClientCountryCode);
    }

    /**
     * @test
     *
     * @return void
     * @throws \Exception
     */
    public function purchase_initiating_success_without_client_state_code_and_client_city(): void
    {
        $payloadWithoutClientStateCode = $this->payload;
        unset($payloadWithoutClientStateCode['clientStateCode'], $payloadWithoutClientStateCode['clientCity']);

        $this->json('POST', $this->validRequestUri(), $payloadWithoutClientStateCode, $this->header());
        $this->assertResponseStatus(Response::HTTP_OK);
    }

    /**
     * @test
     * @depends purchase_initiating_fail_without_client_ip
     * @param array $response Response Result.
     *
     * @return void
     */
    public function purchase_initiating_fail_message_without_client_ip(array $response): void
    {
        $this->assertEquals($response['error'], $this->error_message("client ip"));
    }

    /**
     * @test
     *
     * @return void
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_site_id(): void
    {
        $payload_no_addon = $this->payload;
        unset($payload_no_addon['siteId']);
        $this->json('POST', $this->validRequestUri(), $payload_no_addon, $this->header());
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     *
     * @return void
     * @throws \Exception
     */
    public function purchase_initiating_fail_invalid_site_id(): void
    {
        $faker                   = \Faker\Factory::create();
        $payload_invalid_site_id = $this->payload;

        $payload_invalid_site_id['siteId'] = $faker->uuid;
        $this->json('POST', $this->validRequestUri(), $payload_invalid_site_id, $this->header());
        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     *
     * @return void
     * @throws \Exception
     */
    public function purchase_initiating_fail_without_api_key(): void
    {
        $header = $this->header();
        unset($header['x-api-key']);
        $this->json('POST', $this->validRequestUri(), $this->payload, $header);
        $this->assertResponseStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_with_invalid_initial_amount_after_taxes_value(): array
    {
        $invalidPayload                                       = $this->payload;
        $invalidPayload['tax']['initialAmount']['afterTaxes'] = $invalidPayload['amount'] + 1;

        return $this->purchase_initiating_failing($invalidPayload);
    }

    /**
     * @test
     *
     * @return array
     * @throws \Exception
     */
    public function purchase_initiating_fail_with_invalid_rebill_amount_after_taxes_value(): array
    {
        $invalidPayload                                      = $this->payload;
        $invalidPayload['tax']['rebillAmount']['afterTaxes'] = $invalidPayload['rebillAmount'] + 1;

        return $this->purchase_initiating_failing($invalidPayload);
    }

    /**
     * @test
     * @return array
     */
    public function it_handles_empty_payload_without_db_queries(): array
    {
        $doctrine = $this->app['registry']->getManager('mysql-readonly');

        $logger = new \Doctrine\DBAL\Logging\DebugStack();
        $doctrine->getConnection()
            ->getConfiguration()
            ->setSQLLogger($logger);

        $response = $this->json(
            'POST',
            $this->validRequestUri(),
            $payload = [],
            $this->header()
        );
        $this->assertSame(0, $logger->currentQuery);

        $response->assertResponseStatus(Response::HTTP_BAD_REQUEST);

        return json_decode($this->response->getContent(), true);
    }
}
