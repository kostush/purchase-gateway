<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use JsonSerializable;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;

/**
 * Class GuzzleClient
 * @package ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Postback
 */
class GuzzleClient
{
    private const METHOD = 'POST';

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * GuzzleClient constructor.
     *
     * @param Client $client Guzzle client.
     */
    public function __construct(Client $client)
    {
        $this->guzzleClient = $client;
    }

    /**
     * @param JsonSerializable $dto DTO.
     * @param string           $url Postback destination.
     *
     * @return bool
     * @throws LoggerException
     */
    public function post(JsonSerializable $dto, string $url): bool
    {
        try {
            $response = $this->guzzleClient->request(
                self::METHOD,
                $url,
                [
                    RequestOptions::JSON            => $dto->jsonSerialize(),
                    RequestOptions::ALLOW_REDIRECTS => false,
                    RequestOptions::CONNECT_TIMEOUT => config('clientpostback.connectionTimeout'),
                    RequestOptions::TIMEOUT         => config('clientpostback.timeout'),
                ]
            );

            Log::info(
                'ClientPostback successfully sent',
                [
                    'url'      => $url,
                    'method'   => self::METHOD,
                    'data'     => $dto->jsonSerialize(),
                    'response' => Psr7\str($response),
                ]
            );
        } catch (RequestException $exception) {
            Log::error(
                'ClientPostback error',
                [
                    'url'      => $url,
                    'error'    => $exception->getMessage(),
                    'request'  => Psr7\str($exception->getRequest()),
                    'response' => ($exception->hasResponse()) ? Psr7\str($exception->getResponse()) : null,
                ]
            );

            return false;
        }

        return true;
    }
}
