<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;

class ThirdPartyTransaction
{

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $billerName;

    /**
     * @var string|null
     */
    private $redirectUrl;

    /**
     * @var array
     */
    private $crossSales;

    /**
     * Transaction constructor.
     * @param null|TransactionId $transactionId Transaction Id
     * @param string             $state         State
     * @param string             $billerName    The biller name used for this transaction
     * @param string|null        $redirectUrl   The third party biller redirect url
     * @param array|null         $crossSales    Cross sales
     * @throws \Exception
     */
    private function __construct(
        ?TransactionId $transactionId,
        string $state,
        string $billerName,
        ?string $redirectUrl,
        ?array $crossSales
    ) {
        $this->transactionId = $transactionId;
        $this->setState($state);
        $this->setBillerName($billerName);
        $this->redirectUrl = $redirectUrl;
        $this->crossSales  = $crossSales;
    }

    /**
     * @param string $billerName The biller name
     * @throws \Exception
     * @return void
     */
    private function setBillerName(string $billerName): void
    {
        $this->billerName = BillerFactoryService::create($billerName)->name();
    }

    /**
     * @param string $state The transaction status
     * @throws \Exception
     * @return void
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @param null|TransactionId $transactionId Transaction Id
     * @param string             $state         StateBi
     * @param string             $billerName    The biller name used for this transaction
     * @param string|null        $redirectUrl   The third party redirect url
     * @param array|null         $crossSales    Cross sales
     * @return ThirdPartyTransaction
     * @throws \Exception
     */
    public static function create(
        ?TransactionId $transactionId,
        string $state,
        string $billerName,
        ?string $redirectUrl = null,
        ?array $crossSales = null
    ): self {
        return new static(
            $transactionId,
            $state,
            $billerName,
            $redirectUrl,
            $crossSales
        );
    }

    /**
     * @return null|TransactionId
     */
    public function transactionId(): ?TransactionId
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function state(): string
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function billerName(): string
    {
        return $this->billerName;
    }

    /**
     * @return string|null
     */
    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @return null|array
     */
    public function crossSales(): ?array
    {
        return $this->crossSales;
    }
}
