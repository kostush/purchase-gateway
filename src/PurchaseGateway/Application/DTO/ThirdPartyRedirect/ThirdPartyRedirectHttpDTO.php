<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyRedirect;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

class ThirdPartyRedirectHttpDTO extends ProcessPurchaseGeneralHttpDTO
{
    /**
     * @var array
     */
    protected $response;

    /**
     * ThirdPartyRedirectHttpDTO constructor.
     * @param PurchaseProcess   $purchaseProcess PurchaseProcess
     * @param TokenGenerator    $tokenGenerator  Token Generator
     * @param CryptService|null $cryptService    Crypt Service
     * @param Site|null         $site            Site
     */
    public function __construct(
        PurchaseProcess $purchaseProcess,
        TokenGenerator $tokenGenerator,
        ?CryptService $cryptService,
        Site $site = null
    ) {
        if (!$purchaseProcess->retrieveMainPurchaseItem()->wasItemPurchasePending()) {
            parent::__construct($purchaseProcess, $tokenGenerator, $site, $cryptService);

            $this->response['redirectUrl'] = $purchaseProcess->redirectUrl();
        } else {
            /**
             * @var Transaction $transaction
             */
            $transaction = $purchaseProcess->retrieveMainPurchaseItem()->lastTransaction();

            $this->response['redirectUrl'] = $transaction->redirectUrl();
        }
    }
}
