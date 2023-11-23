<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\PurchaseProcessCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Returns400Code;
use ProBillerNG\PurchaseGateway\Domain\Returns500Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest;

class ProcessPurchaseController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    private $commandHandler;

    /**
     * ProcessPurchaseController constructor.
     *
     * @param PurchaseProcessCommandHandlerFactory $purchaseProcessHandlerFactory PurchaseProcessCommandHandlerFactory
     * @param TransactionalSession                 $session                       The transactional session
     * @param ProcessPurchaseRequest               $request                       ProcessPurchaseRequest
     */
    public function __construct(
        PurchaseProcessCommandHandlerFactory $purchaseProcessHandlerFactory,
        TransactionalSession $session,
        ProcessPurchaseRequest $request
    ) {
        $payment              = $request->get('payment', null);
        $handler              = $purchaseProcessHandlerFactory->getHandler($payment);
        $this->commandHandler = new TransactionalCommandHandler($handler, $session);
    }

    /**
     * @param ProcessPurchaseRequest $request The request
     *
     * @return JsonResponse
     * @throws Exception
     * @throws Throwable
     */
    public function post(ProcessPurchaseRequest $request)
    {
        Log::info('Beginning the process purchase');

        try {
            $crossSales = $request->selectedCrossSells();

            $command = new ProcessPurchaseCommand(
                $request->site(),
                $request->memberUsername(),
                $request->memberPassword(),
                $request->memberEmail(),
                $request->ccNumber(),
                $request->memberZipCode(),
                $request->cvv(),
                $request->cardExpirationMonth(),
                $request->cardExpirationYear(),
                $request->memberFirstName(),
                $request->memberLastName(),
                $request->getFullAddress(),
                $crossSales,
                $request->memberCity() ?: null,
                $request->memberState() ?: null,
                $request->memberCountryCode() ?: null,
                $request->memberPhone() ?: null,
                $request->bearerToken(),
                $request->decodedToken()->getSessionId(),
                $request->getRequestUri(),
                $request->userAgent() ?? null,
                $request->member(),
                $request->payment(),
                $request->lastFour(),
                $request->paymentTemplateId(),
                $request->ndWidgetData(),
                $request->getClientIp() ?: null,
                $request->paymentMethod() ?: null,
                $request->routingNumber() ?: null,
                $request->accountNumber() ?: null,
                $request->savingAccount() ?: false,
                $request->socialSecurityLast4() ?: null,
                $request->getFraudRequiredHeaders()
            );

            $result = $this->commandHandler->execute($command);

            if ($result instanceof Returns400Code) {
                return $this->error($result, Response::HTTP_BAD_REQUEST);
            }
            if ($result instanceof Returns500Code) {
                return $this->error($result, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return response()->json($result);
        } catch (ValidationException $e) {
            return $this->error($e);
        } catch (UnableToProcessTransactionException $e) {
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            Log::logException($e);
            return $this->error($e);
        }
    }
}
