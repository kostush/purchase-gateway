<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI\Processed;

use ProBillerNG\Logger\Log;

class PaymentCC extends Payment
{
    /**
     * @var string
     */
    protected $first6;

    /**
     * @var string
     */
    protected $last4;

    /**
     * @var string
     */
    protected $expirationMonth;

    /**
     * @var string
     */
    protected $expirationYear;

    /**
     * Payment constructor.
     *
     * @param string $first6          First six
     * @param string $last4           Last four
     * @param string $expirationMonth Expiration month
     * @param string $expirationYear  Expiration year
     */
    private function __construct(
        string $first6,
        string $last4,
        string $expirationMonth,
        string $expirationYear
    ) {
        $this->first6          = $first6;
        $this->last4           = $last4;
        $this->expirationMonth = $expirationMonth;
        $this->expirationYear  = $expirationYear;
    }

    /**
     * @param string $first6          First six
     * @param string $last4           Last four
     * @param string $expirationMonth Expiration month
     * @param string $expirationYear  Expiration year
     * @return PaymentCC
     */
    public static function create(
        string $first6,
        string $last4,
        string $expirationMonth,
        string $expirationYear
    ): self {
        return new static(
            $first6,
            $last4,
            $expirationMonth,
            $expirationYear
        );
    }

    /**
     * This is used only for the PurchaseProcessed BI event
     * It is formatted this way because of the BI event specifications
     * https://wiki.mgcorp.co/pages/viewpage.action?spaceKey=EBS&title=Support+new+data+for+blacklisted+cards
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     */
    public function toArray(): array
    {
        // As part of BG-56133, as discussed with DWS, we should return "null" when expiry date is invalid.
        // This is because some customers bypassed Frontend validation, and sent wrong month/year, which was handled by
        // Transaction Service, and turns out to be an aborted transaction, BUT Purchase_Processed event was generated
        // with wrong expiryDate, and it breaks DWS parsing.
        $expiryDate = $this->expirationMonth . '/' . $this->expirationYear;

        // Using a different logic of TS: https://stash.mgcorp.co/projects/PBNGBE/repos/transaction-service/browse/src/Transaction/Domain/Model/CreditCardInformation.php#156
        // Because the purpose here is not to validate if is in the past or not, but to provide a parsable date format.
        // It's not Purchase Gateway responsibility to know if is in the past or not.
        if (!checkdate((int) $this->expirationMonth, 1, (int) $this->expirationYear)) {
            $expiryDate = null;
            Log::warning(
                'PurchaseProcessed Event was generated with null expiryDate, because we received invalid date. ' .
                'Expiration month: ' . $this->expirationMonth . ', expiration year: ' . $this->expirationYear
            );
        }

        return [
            'first6'     => $this->first6,
            'last4'      => $this->last4,
            'expiryDate' => $expiryDate,
        ];
    }
}
