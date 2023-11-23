<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback;

use App\Jobs\ClientPostbackJob;
use JsonSerializable;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;

/**
 * Class LumenJob
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback
 */
class LumenJob implements PostbackService
{
    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * LumenJob constructor.
     *
     * @param GuzzleClient $client Guzzle Client.
     */
    public function __construct(GuzzleClient $client)
    {
        $this->client = $client;
    }

    /**
     * Execute the job.
     * Note: This is not exposed on PostbackService interface because we want to enforce the usage of the queue,
     * not allowing synchronous calls.
     * The reason is because we don't want to wait the "timeouts" from the client by blocking the purchase process.
     *
     * @param JsonSerializable $dto DTO.
     * @param string           $url Postback destination.
     *
     * @return bool
     * @throws LoggerException
     */
    public function send(JsonSerializable $dto, string $url): bool
    {
        if (!$this->client->post($dto, $url)) {
            Log::warning('ClientPostback re-queued', ['url' => $url, 'data' => $dto->jsonSerialize()]);

            return false;
        }

        return true;
    }

    /**
     * Add request to a queue.
     *
     * @param JsonSerializable $dto DTO.
     * @param string|null      $url Postback destination.
     *
     * @return void
     * @throws LoggerException
     */
    public function queue(JsonSerializable $dto, ?string $url): void
    {
        $data = $dto->jsonSerialize();

        if (empty($url)) {
            Log::warning('ClientPostback empty postback url, cannot postback', ['data' => $data]);

            return;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::warning('ClientPostback invalid postback url, cannot postback', ['url' => $url, 'data' => $data]);

            return;
        }

        Log::info('ClientPostback queued', ['url' => $url, 'data' => $data]);
        dispatch(new ClientPostbackJob($dto, $url));
    }
}
