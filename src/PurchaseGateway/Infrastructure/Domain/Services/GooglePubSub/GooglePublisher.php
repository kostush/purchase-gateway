<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\GooglePubSub;

use Google\Cloud\PubSub\PubSubClient;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Publisher;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class GooglePublisher implements Publisher
{
    /**
     * @var PubSubClient
     */
    private $client;

    /**
     * @var string
     */
    private $topicId;

    /**
     * GooglePubSub constructor.
     * @param PubSubClient $client Google Pub Sub client
     */
    public function __construct(PubSubClient $client, string $topicId)
    {
        $this->client  = $client;
        $this->topicId = $topicId;
    }

    /**
     * @param string    $transactionId       Transaction id
     * @param string    $siteId              Site id
     * @param array     $billerFields        Biller fields
     * @param array     $subsequentOperation Subsequent operation
     * @param array     $paymentInformation  Payment information
     * @param SessionId $sessionId           Session id
     * @return void
     * @throws Exception
     */
    public function publishTransactionToBeVoided(
        string $transactionId,
        string $siteId,
        array $billerFields,
        array $subsequentOperation,
        array $paymentInformation,
        SessionId $sessionId
    ): void {
        $topic = $this->client->topic($this->topicId);

        if (!$topic->exists()) {
            $topic = $this->client->createTopic($this->topicId);
        }

        $data = [
            'transactionId'       => $transactionId,
            'siteId'              => $siteId,
            'billerFields'        => $billerFields,
            'subsequentOperation' => $subsequentOperation,
            'paymentInformation'  => $paymentInformation,
            'sessionId'           => (string) $sessionId,
        ];

        Log::info('Message to publish', $data);

        $messageId = $topic->publish(['data' => json_encode($data)]);

        Log::info('Message has been published with the ID', $messageId);
    }
}
