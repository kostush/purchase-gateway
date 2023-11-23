<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CrossSaleSiteNotExistException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseInit\PurchaseInitCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\InitPurchaseRequest;

class InitPurchaseController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    private $commandHandler;

    /**
     * InitPurchaseController constructor.
     *
     * @param PurchaseInitCommandHandlerFactory $purchaseInitCommandFactory PurchaseInitCommandHandlerFactory
     * @param TransactionalSession              $session                    Session
     * @param InitPurchaseRequest               $request                    InitPurchaseRequest
     */
    public function __construct(
        PurchaseInitCommandHandlerFactory $purchaseInitCommandFactory,
        TransactionalSession $session,
        InitPurchaseRequest $request
    ) {
        $memberId             = $request->get('memberId', null);
        $handler              = $purchaseInitCommandFactory->getHandler($memberId);
        $this->commandHandler = new TransactionalCommandHandler($handler, $session);
    }

    /**
     * @param InitPurchaseRequest $request Request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function post(InitPurchaseRequest $request): JsonResponse
    {
        Log::info('Beginning the init purchase');

        $crossSales = $request->getCrossSales();

        try {
            return new JsonResponse(
                $this->commandHandler->execute(
                    new PurchaseInitCommand(
                        $request->getSite(),
                        $request->getAmount(),
                        $request->getInitialDays(),
                        $request->getRebillDays(),
                        $request->getRebillAmount(),
                        $request->getCurrency(),
                        $request->getBundleId(),
                        $request->getAddonId(),
                        $request->getClientIp(),
                        $request->getPaymentType(),
                        $request->getClientCountryCode(),
                        $request->getSessionId(),
                        $request->getAtlasCode(),
                        $request->getAtlasData(),
                        $request->getPublicKeyIndex(),
                        $request->getTax(),
                        $crossSales,
                        $request->getIsTrial(),
                        $request->getMemberId(),
                        $request->getSubscriptionId(),
                        $request->getEntrySiteId(),
                        $request->getForceCascade(),
                        $request->getPaymentMethod(),
                        $request->getTrafficSource(),
                        $request->getRedirectUrl(),
                        $request->getPostbackUrl(),
                        $request->getFraudRequiredHeaders(),
                        $request->getSkipVoid()
                    )
                )
            );
        } catch (ValidationException $e) {
            return $this->error($e);
        } catch (NotFoundException $e) {
            return $this->error($e, Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
