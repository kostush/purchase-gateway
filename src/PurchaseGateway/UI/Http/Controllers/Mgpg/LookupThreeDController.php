<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Lcobucci\JWT\Parser;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProbillerMGPG\Purchase\Lookup3ds\CardInformation;
use ProbillerMGPG\Purchase\Lookup3ds\Lookup3dsRequest;
use ProbillerMGPG\Purchase\Lookup3ds\Payment;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Exceptions\UnableToProcessTransactionException;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\LoookupRequest;
use Ramsey\Uuid\Uuid;

class LookupThreeDController extends Controller
{
    /**
     * @var MgpgClientApi
     */
    protected $clientApi;

    /**
     * @var ProcessPurchaseDTOAssembler
     */
    protected $assembler;

    /**
     * LookupThreeDController constructor.
     * @param MgpgClientApi               $clientApi
     * @param ProcessPurchaseDTOAssembler $assembler
     */
    public function __construct(
        MgpgClientApi $clientApi,
        ProcessPurchaseDTOAssembler $assembler
    ) {
        $this->clientApi = $clientApi;
        $this->assembler = $assembler;
    }

    /**
     * @param LoookupRequest                 $request The request
     *
     * @param ProcessPurchaseMgpgToNgService $mgpgToNgService
     * @return JsonResponse
     * @throws Exception
     */
    public function post(
        LoookupRequest $request,
        ProcessPurchaseMgpgToNgService $mgpgToNgService
    ) {
        Log::info('MGPGLookup3DsAdaptor Beginning the 3Ds Lookup');

        try {
            $command = $this->createCommand($request);

            $lookupRequest = new Lookup3dsRequest(
                (string) $request->site()->siteId(),
                $request->deviceFingerprintingId(),
                new Payment(
                    new CardInformation(
                        $request->ccNumber(),
                        $request->cvv(),
                        $request->cardExpirationMonth(),
                        $request->cardExpirationYear()
                    )
                )
            );

            Log::info('MGPGLookup3DsAdaptor Prepared request', ['payload' => $lookupRequest->toArray()]);

            $mgpgResponse = $this->clientApi->lookup3ds(
                $lookupRequest,
                $command->getMgpgAuthToken(),
                $command->getCorrelationId(),
                $command->getMgpgSessionId()
            );

            Log::info('MGPGLookup3DsAdaptor Response received from MGPG', ['response' => json_encode($mgpgResponse)]);

            if ($mgpgResponse instanceof ErrorResponse) {
                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            $purchaseProcess = new PurchaseProcess($mgpgResponse->purchaseProcess, $command);

            $dto = $this->assembler->assemble($purchaseProcess);

            $mgpgToNgService->queuePostback($purchaseProcess, $dto, $command->getPostbackUrl());

            return response()->json($dto);

        } catch (ErrorResponseException|ValidationException $e) {
            return $this->error($e);
        } catch (UnableToProcessTransactionException $e) {
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->error($e);
        }
    }

    /**
     * @param LoookupRequest $request
     * @return ProcessPurchaseCommand
     * @throws \Exception
     */
    protected function createCommand(LoookupRequest $request): ProcessPurchaseCommand
    {
        $fallbackPostbackUrl = $request->attributes->get('site')->postbackUrl();
        $token               = (new Parser())->parse($request->bearerToken());

        return new ProcessPurchaseCommand(
            $request->attributes->get('sessionId'),
            $request->headers->get('X-CORRELATION-ID'),
            $token->getClaim('X-Mgpg-Session-Id'),
            (int) $token->getClaim('X-Public-Key-Id'),
            $token->getClaim('X-Postback-Url') ?? $fallbackPostbackUrl,
            $token->getClaim('X-Return-Url'),
            $token->getClaim('X-Mgpg-Auth-Token')
        );
    }
}
