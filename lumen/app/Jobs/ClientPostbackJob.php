<?php
declare(strict_types=1);

namespace App\Jobs;

use Exception;
use JsonSerializable;
use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;

/**
 * Class ClientPostbackJob
 * @package App\Jobs
 */
class ClientPostbackJob extends Job
{
    /**
     * @var ProcessPurchaseGeneralHttpDTO
     */
    private $dto;

    /**
     * @var string
     */
    private $url;

    /**
     * The number of times the job may be attempted.
     * This is a laravel built-in property, so we should not rename it.
     *
     * @var int
     */
    public $tries;

    /**
     * The time in seconds between every attempt.
     *
     * @var int
     */
    public $delayBeforeRetryInSeconds;

    /**
     * ClientPostbackJob constructor.
     *
     * @param JsonSerializable $dto DTO.
     * @param string           $url Postback destination.
     */
    public function __construct(JsonSerializable $dto, string $url)
    {
        $this->tries                     = config('clientpostback.maxNumberOfAttempts');
        $this->delayBeforeRetryInSeconds = config('clientpostback.delayBeforeRetryInSeconds');
        $this->dto                       = $dto;
        $this->url                       = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle(): void
    {
        $postbackService = app(PostbackService::class);

        if (!$postbackService->send($this->dto, $this->url)) {
            Log::info('ClientPostback attempt', ['number' => $this->attempts()]);

            if ($this->attempts() === $this->tries) {
                Log::error(
                    'ClientPostback reached the limit of attempts, the postback could not be sent.',
                    ['limit' => $this->tries]
                );

                // This is the mechanism to prevent on sending it to failed jobs, if we call "release" again, it will
                // throw Illuminate\Queue\MaxAttemptsExceededException and goes to failed job. We don't want that.
                // We only want failed jobs for unexpected issues, and this limit attempts is an expected behaviour.
                return;
            }

            $this->release($this->delayBeforeRetryInSeconds);
        }
    }

    /**
     * The job failed to process.
     *
     * @param Exception $exception
     *
     * @return void
     * @throws LoggerException
     */
    public function failed(Exception $exception)
    {
        Log::critical('ClientPostback failed', ['error' => $exception->getMessage()]);
    }
}
