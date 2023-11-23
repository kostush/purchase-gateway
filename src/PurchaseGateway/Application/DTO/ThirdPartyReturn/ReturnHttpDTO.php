<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\ThirdPartyReturn;

use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class ReturnHttpDTO extends ProcessPurchaseGeneralHttpDTO
{
    /**
     * @var array
     */
    protected $response;

    /**
     * ReturnDTO constructor.
     * @param PurchaseProcess   $purchaseProcess Purchase process
     * @param TokenGenerator    $tokenGenerator  Token generator
     * @param Site              $site            Site
     * @param CryptService|null $cryptService    Crypt service
     */
    public function __construct(
        PurchaseProcess $purchaseProcess,
        TokenGenerator $tokenGenerator,
        Site $site,
        ?CryptService $cryptService
    ) {
        parent::__construct($purchaseProcess, $tokenGenerator, $site, $cryptService);

        $this->response['redirectUrl'] = $purchaseProcess->redirectUrl();
    }
}
