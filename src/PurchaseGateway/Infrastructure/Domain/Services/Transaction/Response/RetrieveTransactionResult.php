<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;

abstract class RetrieveTransactionResult
{
    /**
     * @var string
     */
    private $billerId;

    /**
     * @var string
     */
    private $billerName;

    /**
     * @var string|null
     */
    private $transactId;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $siteId;

    /**
     * @var string|null
     */
    private $paymentType;

    /**
     * @var MemberInformation
     */
    private $memberInformation;

    /**
     * @var TransactionInformation
     */
    protected $transactionInformation;

    /**
     * @var BillerFields
     */
    protected $billerFields;


    /**
     * Rocketgate constructor.
     * @param string                 $billerId               Biller id
     * @param string                 $billerName             Biller name
     * @param string|null            $transactId             Biller transaction Id
     * @param string                 $currency               Currency code
     * @param string                 $siteId                 Site UUID
     * @param null|string            $paymentType            Payment type
     * @param MemberInformation      $memberInformation      Member information
     * @param TransactionInformation $transactionInformation Transaction information
     * @param BillerFields           $billerFields           BillerFields
     */
    public function __construct(
        string $billerId,
        string $billerName,
        ?string $transactId,
        string $currency,
        string $siteId,
        ?string $paymentType,
        MemberInformation $memberInformation,
        TransactionInformation $transactionInformation,
        BillerFields $billerFields
    ) {
        $this->billerId               = $billerId;
        $this->transactId             = $transactId;
        $this->currency               = $currency;
        $this->siteId                 = $siteId;
        $this->paymentType            = $paymentType;
        $this->memberInformation      = $memberInformation;
        $this->transactionInformation = $transactionInformation;
        $this->billerName             = $billerName;
        $this->billerFields           = $billerFields;
    }

    /**
     * @return string
     */
    public function billerId(): string
    {
        return $this->billerId;
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
    public function transactId(): ?string
    {
        return $this->transactId;
    }

    /**
     * @return string
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function siteId(): string
    {
        return $this->siteId;
    }

    /**
     * @return null|string
     */
    public function paymentType(): ?string
    {
        return $this->paymentType;
    }

    /**
     * @return MemberInformation
     */
    public function memberInformation(): MemberInformation
    {
        return $this->memberInformation;
    }

    /**
     * @return BillerFields
     */
    abstract public function billerFields();

    /**
     * @return TransactionInformation|CCTransactionInformation
     */
    abstract public function transactionInformation();

    /**
     * @return bool
     */
    abstract public function securedWithThreeD(): bool;

    /**
     * @return int|null
     */
    abstract public function threeDSecureVersion(): ?int;

    /**
     * @return bool
     */
    public function isCheckTransaction(): bool
    {
        return ($this instanceof RocketgateCheckRetrieveTransactionResult);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $ccTransactionDetails = [];

        if ($this->transactionInformation() instanceof CCTransactionInformation) {
            $ccTransactionDetails['first6']              = $this->transactionInformation()->first6();
            $ccTransactionDetails['last4']               = $this->transactionInformation()->last4();
            $ccTransactionDetails['cardExpirationYear']  = $this->transactionInformation()->cardExpirationYear();
            $ccTransactionDetails['cardExpirationMonth'] = $this->transactionInformation()->cardExpirationMonth();
            $ccTransactionDetails['paymentType']         = $this->transactionInformation()->paymentType();
        }

        return [
            'billerId'               => $this->billerId(),
            'billerName'             => $this->billerName(),
            'transactionId'          => $this->transactId(),
            'currency'               => $this->currency(),
            'siteId'                 => $this->siteId(),
            'paymentType'            => $this->paymentType(),
            'memberInformation'      => [
                'email'       => $this->memberInformation()->email(),
                'phoneNumber' => $this->memberInformation()->phoneNumber(),
                'firstName'   => $this->memberInformation()->firstName(),
                'lastName'    => $this->memberInformation()->lastName(),
                'address'     => $this->memberInformation()->address(),
                'city'        => $this->memberInformation()->city(),
                'state'       => $this->memberInformation()->state(),
                'zip'         => $this->memberInformation()->zip(),
                'country'     => $this->memberInformation()->country(),
                'name'        => $this->memberInformation()->name(),
            ],
            'billerFields'           => $this->billerFields()->toArray(),
            'transactionInformation' => [
                'transactionId'            => $this->transactionInformation()->transactionId(),
                'amount'                   => $this->transactionInformation()->amount(),
                'status'                   => $this->transactionInformation()->status(),
                'createdAt'                => $this->transactionInformation()->createdAt(),
                'rebillAmount'             => $this->transactionInformation()->rebillAmount(),
                'rebillFrequency'          => $this->transactionInformation()->rebillFrequency(),
                'rebillStart'              => $this->transactionInformation()->rebillStart(),
                'isNsf'                    => $this->transactionInformation()->isNsf(),
                'CCTransactionInformation' => $ccTransactionDetails,
            ],
            'securedWithThreeD'      => $this->securedWithThreeD(),
            'threeDSecureVersion'    => $this->threeDSecureVersion(),
            'isCheckTransaction'     => $this->isCheckTransaction(),
        ];
    }
}
