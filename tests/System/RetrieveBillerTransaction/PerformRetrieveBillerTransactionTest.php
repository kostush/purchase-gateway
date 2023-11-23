<?php

namespace Tests\System\RetrieveBillerTransaction;

use Faker\Provider\Uuid;
use Illuminate\Http\Response;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;

class PerformRetrieveBillerTransactionTest extends ProcessPurchaseBase
{
    /**
     * @var string
     */
    private $uri = '/api/v1/billerTransaction/search/session/5531d782-2956-11e9-b210-d663bd873d93';

    /**
     * @var array
     */
    private $payload;

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_return_transaction_data_when_successful(): array
    {
        /**
         * an initial join has to be created so that the purchase gateway and
         * transaction service databases get populated
         */
        $initPurchase = $this->initPurchaseProcessWithOneCrossSale(false);
        $initPurchase->seeHeader('X-Auth-Token');

        $token = (string) $this->response->headers->get('X-Auth-Token');

        $processPurchase = $this->json(
            'POST',
            $this->validProcessPurchaseRequestUri(),
            $this->processPurchasePayloadWithNoSelectedCrossSale(),
            $this->processPurchaseHeaders($token)
        );

        $response = json_decode($processPurchase->response->getContent(), true);

        $this->payload = [
            'itemId' => $response['itemId']
        ];

        $response = $this->json('GET', $this->uri, $this->payload);

        $this->assertResponseOk();

        return json_decode($response->response->getContent(), true);
    }

    /**
     * @test
     * @depends it_should_return_transaction_data_when_successful
     * @param array $response The returned response
     * @return void
     */
    public function it_should_return_the_transaction_id_when_successful(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends it_should_return_transaction_data_when_successful
     * @param array $response The returned response
     * @return void
     */
    public function it_should_return_the_biller_transaction_when_successful(array $response): void
    {
        $this->assertArrayHasKey('billerTransaction', $response);
    }

    /**
     * @test
     * @depends it_should_return_transaction_data_when_successful
     * @param array $response The returned response
     * @return void
     */
    public function it_should_return_the_biller_transaction_id_when_successful(array $response): void
    {
        $this->assertArrayHasKey('billerTransactionId', $response['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_transaction_data_when_successful
     * @param array $response The returned response
     * @return void
     */
    public function it_should_return_the_biller_id_when_successful(array $response): void
    {
        $this->assertArrayHasKey('billerId', $response['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_transaction_data_when_successful
     * @param array $response The returned response
     * @return void
     */
    public function it_should_return_the_biller_name_when_successful(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_transaction_data_when_successful
     * @param array $response The returned response
     * @return void
     */
    public function it_should_return_the_biller_fields_when_successful(array $response): void
    {
        $this->assertArrayHasKey('billerFields', $response['billerTransaction']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_error_when_payload_is_invalid(): void
    {
        $this->payload = [
            'itemId' => 'abcd'
        ];

        $this->json('GET', $this->uri, $this->payload);

        $this->assertResponseStatus(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_error_when_item_not_exist(): void
    {
        $this->payload = [
            'itemId' => $this->faker->uuid
        ];

        $this->json('GET', $this->uri, $this->payload);

        $this->assertResponseStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_create_valid_session_id_when_invalid_provided(): void
    {
        $this->payload = ['itemId' => $this->faker->uuid];

        $invalidSessionProvided = '{{some invalid session id}}';

        $response = $this->json(
            'GET',
            '/api/v1/billerTransaction/search/session/'. $invalidSessionProvided,
            $this->payload
        );

        $uri = explode('/', $response->uri);
        $validGeneratedSessionId = end($uri);

        $this->assertNotEquals($invalidSessionProvided, $validGeneratedSessionId);
        $this->assertTrue(\Ramsey\Uuid\Uuid::isValid($validGeneratedSessionId));
    }
}
