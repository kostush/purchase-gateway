<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use AutoMapperPlus\Exception\UnregisteredMappingException;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Laravel\Lumen\Http\ResponseFactory;
use Lcobucci\JWT\Parser;
use Illuminate\Http\Request;
use ProbillerMGPG\Common\Mappings\ClientMapper;
use ProbillerMGPG\Purchase\Process\PurchaseProcessResponse;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler as PurchaseProcessAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ReturnException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\CompletedProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ThirdPartyReturnController extends ThirdPartyController
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
     * @param string  $jwt
     * @param Request $request Request
     *
     * @return Response|ResponseFactory
     * @throws UnregisteredMappingException|Exception
     */
    public function performReturn(string $jwt, Request $request)
    {
        try {
            $token = (new Parser())->parse($jwt);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Log::error('MGPGThirdPartyReturnAdaptor Malformed JWT, cannot decode.');
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
            Log::error('MGPGThirdPartyReturnAdaptor JWT token missing attribute.');
            return response($this->errorRequest($e), SymfonyResponse::HTTP_BAD_REQUEST);
        }

        try {
            Log::info('MGPGThirdPartyReturnAdaptor Beginning 3rd party return');
            $payload = $request->input('payload');
            if ($payload == null) {
                Log::error('MGPGThirdPartyReturnAdaptor Missing `payload` key in body.');
                throw new ReturnException(null);
            }

            $decodedPayload = json_decode($payload, true);

            if ($decodedPayload == null) {
                Log::error('MGPGThirdPartyReturnAdaptor Could not parse payload string.');
                throw new ReturnException(null) ;
            }

            Log::info('MGPGThirdPartyReturnAdaptor Using mgpg-php-sdk to decode payload', compact('payload'));
            $mgpgPurchaseProcess = (ClientMapper::build())->map($decodedPayload, PurchaseProcessResponse::class);

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

            $result = $this->mgpgToNgService->buildReturnDto($purchaseProcess, $dto)->jsonSerialize();
        } catch (ReturnException $e) {
            return response($this->errorRequest($e), SymfonyResponse::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return response($this->serverError(), SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response($this->successRedirectToClient($clientUrl, $result), SymfonyResponse::HTTP_OK);
    }

    /**
     * @param string $clientReturnUrl
     * @param array  $response Response
     *
     * @return View
     */
    private function successRedirectToClient(string $clientReturnUrl, array $response): View
    {
        $params = [
            'clientUrl' => $clientReturnUrl,
            'response'  => $response,
        ];

        return view('thirdParty.redirectToClient', $params);
    }
}
