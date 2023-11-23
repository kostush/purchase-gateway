<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\GooglePubSub;

use Google\Cloud\PubSub\PubSubClient;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\GooglePubSub\GooglePublisher;
use Tests\IntegrationTestCase;

class GooglePublisherTest extends IntegrationTestCase
{
    public const TEST_SUBSCRIPTION = 'test-subscription';

    /**
     * @var array
     */
    private $dataForVoid;

    /**
     * @var PubSubClient
     */
    private $pubSubClient;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->dataForVoid = [
            'transactionId'       => '7780f74b-d88a-405d-993d-48c09fc0e40d',
            'siteId'              => 'f05581a6-a369-477b-aeac-17a483dbf255',
            'billerFields'        => [
                'field1' => '123',
                'field2' => '456',
            ],
            'subsequentOperation' => [
                'field1' => '789',
                'field2' => '123',
            ],
            'paymentInformation'  => [
                'field1' => '456',
                'field2' => '789'
            ],
            'sessionId'           => SessionId::createFromString('3ff71954-fc2a-40da-b5d9-acc00cae28ad')
        ];

        $this->pubSubClient = new PubSubClient(
            [
                'projectId' => env('GOOGLE_CLOUD_PROJECT', 'mg-probiller-dev')
            ]
        );

        $topic = $this->pubSubClient->topic(env('GCLOUD_PUB_SUB_TOPIC_ID','mg-void-legacy'));
        if (!$topic->exists()) {
            $topic = $this->pubSubClient->createTopic(env('GCLOUD_PUB_SUB_TOPIC_ID','mg-void-legacy'));
        }

        $subscription = $topic->subscription(self::TEST_SUBSCRIPTION);
        if (!$subscription->exists()) {
            $subscription->create();
        }
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_successfully_publish_on_pubsub(): void
    {
        $dataForVoid = $this->dataForVoid;

        $googlePublisher = new GooglePublisher(
            $this->pubSubClient,
            env('GCLOUD_PUB_SUB_TOPIC_ID','mg-void-legacy')
        );

        $googlePublisher->publishTransactionToBeVoided(
            $dataForVoid['transactionId'],
            $dataForVoid['siteId'],
            $dataForVoid['billerFields'],
            $dataForVoid['subsequentOperation'],
            $dataForVoid['paymentInformation'],
            $dataForVoid['sessionId']
        );

        $subscription = $this->pubSubClient->subscription(self::TEST_SUBSCRIPTION);
        $messages     = $subscription->pull();

        foreach ($messages as $message) {
            $dataForVoid['sessionId'] = (string) $dataForVoid['sessionId'];

            $messageData = json_decode($message->data(), true);

            if (!isset($messageData['sessionId'])
                || $messageData['sessionId'] !== $dataForVoid['sessionId']
            ) {
                continue;
            }

            $this->assertSame($dataForVoid, $messageData);
        }

        $subscription->acknowledgeBatch($messages);
    }
}
