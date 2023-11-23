<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\BI\CreditCardIsBlacklisted;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\CreditCardIsBlacklisted as CreditCardIsBlacklistedException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;

trait ManageCreditCardBlacklistTrait
{
    /**
     * @var CCForBlackListService
     */
    private $CCForBlackListService;

    /**
     * @param CCForBlackListService $CCForBlackListService Credit card for blacklist service.
     * @return void
     */
    public function init(CCForBlackListService $CCForBlackListService): void
    {
        $this->CCForBlackListService = $CCForBlackListService;
    }

    /**
     * @param InitializedItem $mainItem        Main item.
     * @param array           $crossSales      Cross sales.
     * @param string          $firstSix        First six.
     * @param string          $lastFour        Last four.
     * @param string          $expirationMonth Expiration month.
     * @param string          $expirationYear  Expiration year.
     * @param string          $sessionId       Session id.
     * @return bool
     */
    public function blacklistCreditCardIfNeeded(
        InitializedItem $mainItem,
        array $crossSales,
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId
    ): bool {

        if (!$this->isBlacklistCreditCardFeatureEnabled()) {
            return false;
        }

        $ccAddedToBlacklistForMainItem   = false;
        $ccAddedToBlacklistForCrossSales = false;

        // Call config service for a possible CC blacklist on main item
        if (!$mainItem->wasItemPurchaseSuccessful()) {
            $ccAddedToBlacklistForMainItem = $this->CCForBlackListService->addCCForBlackList(
                $firstSix,
                $lastFour,
                $expirationMonth,
                $expirationYear,
                $sessionId,
                $mainItem->lastTransaction()
            );
        }

        // Call config service for a possible CC blacklist on cross sales
        if (!$this->allCrossSalesWereSuccessful($crossSales)) {
            $ccAddedToBlacklistForCrossSales = $this->CCForBlackListService->addCCForBlackList(
                $firstSix,
                $lastFour,
                $expirationMonth,
                $expirationYear,
                $sessionId,
                $this->firstFailedCrossSaleItem($crossSales)->lastTransaction()
            );
        }

        // return true if the cc was added to blacklist for either the main item
        // or crosssales and false if not
        return $ccAddedToBlacklistForMainItem || $ccAddedToBlacklistForCrossSales;
    }

    /**
     * @param string $firstSix        First six.
     * @param string $lastFour        Last four.
     * @param string $expirationMonth Expiration month.
     * @param string $expirationYear  Expiration year.
     * @param string $sessionId       Session id.
     * @return bool
     */
    public function checkCreditCardForBlacklist(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId
    ): bool {

        if (!$this->isBlacklistCreditCardFeatureEnabled()) {
            return false;
        }

        // Check CC For Blacklist
        if ($this->CCForBlackListService->checkCCForBlacklist(
            $firstSix,
            $lastFour,
            $expirationMonth,
            $expirationYear,
            $sessionId
        )
        ) {
            // Remove the redis key so that the client is able to try again with another card
            $this->removeKeyOfFinishedProcess($sessionId);

            return true;
        }

        return false;
    }

    /**
     * @param string      $firstSix        First six.
     * @param string      $lastFour        Last four.
     * @param string      $expirationMonth Expiration month.
     * @param string      $expirationYear  Expiration year.
     * @param string      $sessionId       Session id.
     * @param string      $email           Email.
     * @param string      $amount          Amount.
     * @param string|null $memberId        Member id.
     * @return void
     * @throws CreditCardIsBlacklistedException
     * @throws Exception
     * @throws \Exception
     */
    public function sendBiEventIfCreditCardIsBlacklisted(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId,
        string $email,
        string $amount,
        ?string $memberId
    ): void {
        $isCreditCardBlacklisted = $this->checkCreditCardForBlacklist(
            $firstSix,
            $lastFour,
            $expirationMonth,
            $expirationYear,
            $sessionId
        );

        if ($isCreditCardBlacklisted) {
            $this->purchaseProcess->setCreditCardWasBlacklisted(true);

            // Credit card is blacklisted BI event
            $creditCardIsBlacklisted = new CreditCardIsBlacklisted(
                $firstSix,
                $lastFour,
                $expirationMonth,
                $expirationYear,
                $email,
                $amount,
                $sessionId,
                $memberId
            );

            $this->biLoggerService->write($creditCardIsBlacklisted);

            throw new CreditCardIsBlacklistedException();
        }
    }

    /**
     * @param array $crossSales Cross sales.
     * @return bool
     */
    protected function allCrossSalesWereSuccessful(array $crossSales): bool
    {
        /** @var InitializedItem $crossSale */
        foreach ($crossSales as $crossSale) {
            if (!$crossSale->wasItemPurchaseSuccessful()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $crossSales Cross sales.
     * @return InitializedItem|null
     */
    protected function firstFailedCrossSaleItem(array $crossSales): ?InitializedItem
    {
        /** @var InitializedItem $crossSale */
        foreach ($crossSales as $crossSale) {
            if (!$crossSale->wasItemPurchaseSuccessful()) {
                return $crossSale;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    private function isBlacklistCreditCardFeatureEnabled(): bool
    {
        return filter_var(
            env('BLACKLIST_CREDIT_CARD_FEATURE_IS_ENABLED', false),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
