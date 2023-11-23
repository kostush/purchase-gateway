<?php

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\Mgpg;

class MgpgPostbackResponseDto implements \JsonSerializable
{
    /** @var array */
    private $response;

    /**
     * PostbackResponseDto constructor.
     *
     * @param array $response Response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->response;
    }
}