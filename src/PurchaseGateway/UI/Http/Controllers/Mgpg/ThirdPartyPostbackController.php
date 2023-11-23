<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Laravel\Lumen\Http\ResponseFactory;
use Lcobucci\JWT\Parser;
use ProbillerMGPG\Common\Mappings\ClientMapper;
use ProbillerMGPG\Purchase\Common\NextAction;
use ProbillerMGPG\Purchase\Process\PurchaseProcessResponse;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler as PurchaseProcessAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NoBodyOrHeaderReceivedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\CompletedProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\FailedDependencyException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Mgpg\PostbackRequest;

class ThirdPartyPostbackController extends ThirdPartyController
{
    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var ProcessPurchaseMgpgToNgService
     */
    protected $mgpgToNgService;

    /**
     * @param TokenGenerator                 $tokenGenerator
     * @param CryptService                   $cryptService
     * @param ProcessPurchaseMgpgToNgService $mgpgToNgService
     */
    public function __construct(
        TokenGenerator $tokenGenerator,
        CryptService $cryptService,
        ProcessPurchaseMgpgToNgService $mgpgToNgService
    ) {
        $this->tokenGenerator  = $tokenGenerator;
        $this->cryptService    = $cryptService;
        $this->mgpgToNgService = $mgpgToNgService;
    }

    /**
     * @param string          $jwt
     * @param PostbackRequest $request Request
     *
     * @return JsonResponse|ResponseFactory
     * @throws Exception
     */
    public function performPostback(string $jwt, PostbackRequest $request)
    {
        try {
            $token = (new Parser())->parse($jwt);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Log::error('MGPGThirdPartyPostbackAdaptor Malformed JWT, cannot decode.');

            return response($this->errorRequest($e), SymfonyResponse::HTTP_BAD_REQUEST);
        }

        try {
            $clientUrl     = $this->cryptService->decrypt($token->getClaim('clientUrl'));
            $sessionId     = $this->cryptService->decrypt($token->getClaim('sessionId'));
            $correlationId = $this->cryptService->decrypt($token->getClaim('correlationId'));
            $publicKeyId   = (int) $this->cryptService->decrypt($token->getClaim('publicKeyId'));

            // Only at this point can we update the logger to use the sessionId that originated the purchase.
            Log::updateSessionId($sessionId);
            Log::updateCorrelationId($correlationId);

        } catch (\OutOfBoundsException $e) {
            Log::error('MGPGThirdPartyPostbackAdaptor JWT token missing attribute.');

            return response($this->errorRequest($e), SymfonyResponse::HTTP_BAD_REQUEST);
        }

        try {
            Log::info('MGPGThirdPartyPostbackAdaptor Beginning postback',
                [
                    'mgpgPostback' => json_encode($request->toArray())
                ]
            );

            $mgpgPurchaseProcess = (ClientMapper::build())->map(
                [
                    'nextAction' => new NextAction(),
                    'invoice'    => $request->getInvoice(),
                    'digest'     => $request->getDigest(),
                ],
                PurchaseProcessResponse::class
            );

            $command         = new CompletedProcessPurchaseCommand($sessionId, $correlationId, $publicKeyId);
            $purchaseProcess = new PurchaseProcess($mgpgPurchaseProcess, $command);
            $dtoAssembler    = new PurchaseProcessAssembler(
                $this->tokenGenerator,
                app(ConfigService::class)->getSite(
                    $mgpgPurchaseProcess->invoice->charges[0]->siteId
                ),
                $this->cryptService
            );

            $dto = $this->mgpgToNgService->translate($purchaseProcess, $dtoAssembler);

            $result = $this->mgpgToNgService->buildPostbackDto($purchaseProcess, $dto)->jsonSerialize();
        } catch (\InvalidArgumentException $e) {
            throw new InvalidUUIDException($e);
        } catch (NoBodyOrHeaderReceivedException $e) {
            throw new NoBodyOrHeaderReceivedException($e);
        } catch (SessionNotFoundException | TransactionNotFoundException $e) {
            return $this->error($e, Response::HTTP_NOT_FOUND);
        } catch (TransactionAlreadyProcessedException $e){
            return $this->error($e, Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (FailedDependencyException $e) {
            return $this->error($e, Response::HTTP_FAILED_DEPENDENCY);
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->internalServerError($e);
        }

        try {
            $this->mgpgToNgService->queuePostback($purchaseProcess, $dto, $clientUrl);
        } catch (\Exception $e) {
            Log::error('Error occurred while sending postback to queue', ['message' => $e->getMessage()]);

            return $this->internalServerError($e);
        }

        return response()->json($result);
    }
}
