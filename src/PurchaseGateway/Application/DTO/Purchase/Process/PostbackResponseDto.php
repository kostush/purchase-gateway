<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;

class PostbackResponseDto implements \JsonSerializable
{

    /** @var array */
    private $response;

    /**
     * PostbackResponseDto constructor.
     * @param array $response Response
     */
    private function __construct(array $response)
    {
        $this->response = $response;
    }

    /**
     * @param ProcessPurchaseGeneralHttpDTO $dto            ProcessPurchaseGeneralHttpDTO
     * @param TokenGenerator                $tokenGenerator Token Generator
     * @param int                           $publicKeyIndex Public Key Index
     * @param SessionId                     $sessionId      Session Id
     * @param InitializedItem               $mainPurchase   Main Purchase
     * @param array                         $crossSales     Cross Sales
     * @param UserInfo|null                 $userInfo       User Info
     * @return PostbackResponseDto
     */
    public static function createFromResponseData(
        ProcessPurchaseGeneralHttpDTO $dto,
        TokenGenerator $tokenGenerator,
        int $publicKeyIndex,
        SessionId $sessionId,
        InitializedItem $mainPurchase,
        array $crossSales,
        ?UserInfo $userInfo = null
    ): PostbackResponseDto {
        $response = $dto->jsonSerialize();
        unset($response['digest']);
        $response['sessionId']     = (string) $sessionId;

        if (!is_null($userInfo)) {
            $response['username'] = (string) $userInfo->username();
            $response['password'] = (string) $userInfo->password();
        }

        $digest = $tokenGenerator->generateWithPublicKey(
            $dto->site(),
            $publicKeyIndex,
            [
                'hash' => hash('sha512', json_encode($response))
            ]
        );

        $response['digest'] = (string) $digest;

        return new self($response);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->response;
    }
}
