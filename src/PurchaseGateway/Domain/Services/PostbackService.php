<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use JsonSerializable;

/**
 * Interface PostbackService
 * @package ProBillerNG\PurchaseGateway\Domain\Services
 */
interface PostbackService
{
    /**
     * Add request to a queue.
     *
     * @param JsonSerializable $dto DTO.
     * @param string|null      $url Postback destination.
     *
     * @return void
     */
    public function queue(JsonSerializable $dto, ?string $url): void;
}
