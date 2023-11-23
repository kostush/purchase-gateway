<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI\Processed;

use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;

class PaymentCheck extends Payment
{
    /**
     * @var string
     */
    protected $routingNumber;

    /**
     * @var string
     */
    protected $accountNumber;

    /**
     * @var string
     */
    protected $socialSecurityLast4;

    /**
     * PaymentCheck constructor.
     *
     * @param string $routingNumber
     * @param string $accountNumber
     * @param string $socialSecurityLast4
     */
    private function __construct(
        string $routingNumber,
        string $accountNumber,
        string $socialSecurityLast4
    ) {
        $this->routingNumber = $routingNumber;
        $this->accountNumber = $accountNumber;
        $this->socialSecurityLast4 = $socialSecurityLast4;
    }

    /**
     * @param string $routingNumber
     * @param string $accountNumber
     * @param string $socialSecurityLast4
     *
     * @return PaymentCheck
     */
    public static function create(
        string $routingNumber,
        string $accountNumber,
        string $socialSecurityLast4
    ): self {
        return new static(
            $routingNumber,
            $accountNumber,
            $socialSecurityLast4
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $returnArray = parent::toArray();

        $returnArray['routingNumber']       = $this->routingNumber;
        $returnArray['accountNumber']       = ChequePaymentInfo::obfuscateAccountNumber($this->accountNumber);
        $returnArray['socialSecurityLast4'] = $this->socialSecurityLast4;

        return $returnArray;
    }
}
