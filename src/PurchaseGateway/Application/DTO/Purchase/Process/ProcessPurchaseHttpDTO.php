<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidStateException;
use ProBillerNG\PurchaseGateway\Domain\Model\GenericPurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\NextActionProcessFactory;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\AbstractState;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Valid;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;

abstract class ProcessPurchaseHttpDTO implements \JsonSerializable
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @var GenericPurchaseProcess
     */
    protected $purchaseProcess;

    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var Site
     */
    private $site;

    /**
     * ProcessPurchaseGeneralHttpDTO constructor.
     * @param GenericPurchaseProcess $purchaseProcess GenericPurchaseProcess
     * @param TokenGenerator         $tokenGenerator  Token Generator
     * @param Site                   $site            Site
     * @param CryptService|null      $cryptService    Crypt Service
     */
    public function __construct(
        GenericPurchaseProcess $purchaseProcess,
        TokenGenerator $tokenGenerator,
        Site $site,
        ?CryptService $cryptService
    ) {
        $this->purchaseProcess = $purchaseProcess;
        $this->tokenGenerator  = $tokenGenerator;
        $this->site            = $site;
        $this->cryptService    = $cryptService;

        $this->responseData();
        $this->addDigestInResponse();
    }

    /**
     * @return void
     */
    abstract protected function responseData(): void;

    /**
     * @return void
     */
    private function addDigestInResponse(): void
    {
        $digest = $this->tokenGenerator->generateWithPublicKey(
            $this->site,
            $this->purchaseProcess->publicKeyIndex(),
            [
                'hash' => hash('sha512', json_encode($this->response))
            ]
        );


        $this->response['digest'] = (string) $digest;
    }

    /**
     * @return TokenGenerator
     */
    public function tokenGenerator(): TokenGenerator
    {
        return $this->tokenGenerator;
    }

    /**
     * @return Site
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->response;
    }

    /**
     * @return array
     * @throws InvalidStateException
     * @throws Exception
     */
    protected function buildNextAction(): array
    {
        // For MGPG Adaptor, we create the nextAction by disregarding all normal NG logic since the purchase
        // is made on MGPG and we act as a proxy.
        if ($this->purchaseProcess->isMgpgProcess()) {
            return $this->purchaseProcess->nextAction();
        }

        /** @var AbstractState $purchaseProcessState */
        $purchaseProcessState = $this->purchaseProcess->state();

        /** @var Cascade|null $cascade */
        $cascade = $this->purchaseProcess->cascade();

        /** @var Transaction $mainItemLastTransaction */
        $mainItemLastTransaction = $this->purchaseProcess->retrieveMainPurchaseItem()->lastTransaction();

        /** @var string|null $deviceCollectionUrl */
        $deviceCollectionUrl = $mainItemLastTransaction ? $mainItemLastTransaction->deviceCollectionUrl() : null;

        /** @var string|null $deviceCollectionJwt */
        $deviceCollectionJwt = $mainItemLastTransaction ? $mainItemLastTransaction->deviceCollectionJwt() : null;

        $jwt = (string) $this->tokenGenerator->generateWithGenericKey(
            [
                'sessionId' => $this->cryptService->encrypt((string) $this->purchaseProcess->sessionId())
            ]
        );

        $urlParams = [
            'jwt' => $jwt
        ];

        if ($purchaseProcessState instanceof Valid && $cascade && $cascade->isTheNextBillerThirdParty()) {
            $thirdParty                 = ThirdParty::create(route('thirdParty.redirect', $urlParams));
            $thirdPartyRedirectUrlExist = $this->purchaseProcess->redirectUrl() !== null;
        }

        return NextActionProcessFactory::create(
            $purchaseProcessState,
            route('threed.authenticate', $urlParams),
            $thirdParty ?? null,
            $thirdPartyRedirectUrlExist ?? null,
            $deviceCollectionUrl,
            $deviceCollectionJwt,
            $mainItemLastTransaction,
            null,
            null
        )->toArray();
    }
}
