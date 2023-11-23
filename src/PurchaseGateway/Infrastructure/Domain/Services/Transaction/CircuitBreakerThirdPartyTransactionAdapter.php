<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\PerformThirdPartyTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;

class CircuitBreakerThirdPartyTransactionAdapter extends CircuitBreaker implements PerformThirdPartyTransactionAdapter
{
    /**
     * @var ThirdPartyPerformTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerThirdPartyTransactionAdapter constructor.
     * @param CommandFactory                      $commandFactory Command
     * @param ThirdPartyPerformTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        ThirdPartyPerformTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param Site                $site              The site id.
     * @param array               $crossSaleSites    Cross sales sites.
     * @param Biller              $biller            The biller id.
     * @param CurrencyCode        $currencyCode      The currency code.
     * @param UserInfo            $userInfo          The user info.
     * @param ChargeInformation   $chargeInformation The charge information.
     * @param PaymentInfo         $paymentInfo       The payment info.
     * @param BillerMapping       $billerMapping     The biller mapping.
     * @param SessionId           $sessionId         The session id.
     * @param string              $redirectUrl       Redirect url.
     * @param TaxInformation|null $taxInformation    Tax information.
     * @param array|null          $crossSales        Cross sales list.
     * @param string|null         $paymentMethod     Payment method.
     * @param string|null         $billerMemberId    Biller member id.
     * @return ThirdPartyTransaction
     */
    public function performTransaction(
        Site $site,
        array $crossSaleSites,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId,
        string $redirectUrl,
        ?TaxInformation $taxInformation,
        ?array $crossSales,
        ?string $paymentMethod,
        ?string $billerMemberId
    ): ThirdPartyTransaction {
        $command = $this->commandFactory->getCommand(
            CreatePerformThirdPartyTransactionCommand::class,
            $this->adapter,
            $site,
            $crossSaleSites,
            $biller,
            $currencyCode,
            $userInfo,
            $chargeInformation,
            $paymentInfo,
            $billerMapping,
            $sessionId,
            $redirectUrl,
            $taxInformation,
            $crossSales,
            $paymentMethod,
            $billerMemberId
        );

        return $command->execute();
    }
}
