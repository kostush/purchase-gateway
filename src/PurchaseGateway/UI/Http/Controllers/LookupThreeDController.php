<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\Lookup\LookupThreeDCommand;
use ProBillerNG\PurchaseGateway\Application\Services\Lookup\LookupThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Returns400Code;
use ProBillerNG\PurchaseGateway\Domain\Returns500Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\LoookupRequest;

class LookupThreeDController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    private $commandHandler;

    /**
     * LookupThreeDController constructor.
     * @param LookupThreeDCommandHandler $handler Lookup Handler
     * @param TransactionalSession       $session Session
     */
    public function __construct(LookupThreeDCommandHandler $handler, TransactionalSession $session)
    {
        $this->commandHandler = new TransactionalCommandHandler($handler, $session);
    }

    /**
     * @param LoookupRequest $request The request
     *
     * @return JsonResponse
     * @throws Exception
     * @throws \Throwable
     */
    public function post(LoookupRequest $request)
    {
        Log::info('Beginning the threeDS lookup');

        try {

            $command = new LookupThreeDCommand(
                $request->site(),
                $request->ccNumber(),
                $request->cvv(),
                $request->cardExpirationMonth(),
                $request->cardExpirationYear(),
                $request->decodedToken()->getSessionId(),
                $request->deviceFingerprintingId()
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
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->error($e);
        }
    }
}
