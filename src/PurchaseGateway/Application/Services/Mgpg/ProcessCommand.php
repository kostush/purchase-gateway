<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Mgpg;

abstract class ProcessCommand
{
    /**
     * @var array
     */
    protected $selectedChargeIds;

    /**
     * @var string
     */
    protected $ngSessionId;

    /**
     * @var string
     */
    protected $mgpgSessionId;

    /**
     * @var string
     */
    protected $correlationId;

    /**
     * @var int
     */
    protected $publicKeyId;

    /**
     * @var string
     */
    protected $postbackUrl;

    /**
     * @var string
     */
    protected $returnUrl;
    /**
     * @var string
     */
    protected $mgpgAuthToken;

    /**
     * ProcessPurchaseCommand constructor.
     * @param string      $ngSessionId
     * @param string      $correlationId
     * @param string      $mgpgSessionId
     * @param int         $publicKeyId
     * @param string      $postbackUrl Either provided by client or retrieve from site config
     * @param string      $returnUrl
     * @param string|null $mgpgAuthToken
     * @param array       $selectedChargeIds
     */
    public function __construct(
        string $ngSessionId,
        string $correlationId,
        string $mgpgSessionId,
        int $publicKeyId,
        string $postbackUrl,
        string $returnUrl,
        ?string $mgpgAuthToken = null,
        array $selectedChargeIds = []
    ) {
        $this->ngSessionId       = $ngSessionId;
        $this->mgpgSessionId     = $mgpgSessionId;
        $this->correlationId     = $correlationId;
        $this->publicKeyId       = $publicKeyId;
        $this->postbackUrl       = $postbackUrl;
        $this->mgpgAuthToken     = $mgpgAuthToken;
        $this->returnUrl         = $returnUrl;
        $this->selectedChargeIds = $selectedChargeIds;
    }

    /**
     * @return array
     */
    public function getSelectedChargeIds(): array
    {
        return $this->selectedChargeIds;
    }

    /**
     * @return string
     */
    public function getNgSessionId(): string
    {
        return $this->ngSessionId;
    }

    /**
     * @return string
     */
    public function getMgpgSessionId(): string
    {
        return $this->mgpgSessionId;
    }

    /**
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @return int
     */
    public function getPublicKeyId(): int
    {
        return $this->publicKeyId;
    }

    /**
     * @return string
     */
    public function getPostbackUrl(): string
    {
        return $this->postbackUrl;
    }

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * @return string|null
     */
    public function getMgpgAuthToken(): ?string
    {
        return $this->mgpgAuthToken;
    }
}
