<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Lcobucci\JWT\Parser;
use ProbillerMGPG\ClientApi as MgpgClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProbillerMGPG\Purchase\Complete3ds\Complete3dsRequest;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\Mgpg\CompleteThreeDCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingParesAndMdException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Mgpg\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RenderGateway;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\ProcessPurchaseMgpgToNgService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;

class CompleteThreeDController extends Controller
{
    /**
     * @var CompleteThreeDCommandDTOAssembler
     */
    protected $dtoAssembler;
    /**
     * @var MgpgClientApi
     */
    protected $mgpgClient;
    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var ProcessPurchaseMgpgToNgService
     */
    protected $mgpgToNgService;

    /**
     * CompleteThreeDController constructor.
     *
     * @param CompleteThreeDCommandDTOAssembler $dtoAssembler
     * @param MgpgClientApi                     $clientApi Mgpg SDK API
     * @param CryptService                      $cryptService
     * @param ProcessPurchaseMgpgToNgService    $mgpgToNgService
     */
    public function __construct(
        CompleteThreeDCommandDTOAssembler $dtoAssembler,
        MgpgClientApi $clientApi,
        CryptService $cryptService,
        ProcessPurchaseMgpgToNgService $mgpgToNgService
    ) {
        $this->dtoAssembler    = $dtoAssembler;
        $this->mgpgClient      = $clientApi;
        $this->cryptService    = $cryptService;
        $this->mgpgToNgService = $mgpgToNgService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function completePurchase(Request $request)
    {
        Log::info('MGPGComplete3DsAdaptor Beginning 3Ds complete');

        try {
            $token   = (new Parser())->parse($request->jwt);
            $termUrl = $token->getClaim('termUrl');
            $paRes   = $request->get('PaRes');
            $md      = $request->get('MD');

            $command = new ProcessPurchaseCommand(
                $request->attributes->get('sessionId'),
                $request->headers->get('X-CORRELATION-ID'),
                $this->cryptService->decrypt($token->getClaim('mgpgSessionId')),
                (int) $this->cryptService->decrypt($token->getClaim('publicKeyIndex')),
                $this->cryptService->decrypt($token->getClaim('postbackUrl')),
                $this->cryptService->decrypt($token->getClaim('returnUrl'))
            );

            if (empty($paRes) && empty($md)) {
                Log::info('MGPGComplete3DsAdaptor Missing 3D Complete paRes or MD parameter.');
                throw new MissingParesAndMdException(
                    RenderGateway::create()->toArray(),
                    $command->getReturnUrl()
                );
            }

            $mgpgRequest = new Complete3dsRequest($request->get('PaRes'), $request->get('MD'));

            Log::info('MGPGComplete3DsAdaptor Calling MGPG',
                [
                    'termUrl' => $termUrl,
                    'payload' => $mgpgRequest->toArray()
                ]
            );

            $mgpgResponse = $this->mgpgClient->complete3ds($mgpgRequest, $termUrl);

            Log::info('MGPGComplete3DsAdaptor MGPG Response', ['response' => json_encode($mgpgResponse)]);

            if ($mgpgResponse instanceof ErrorResponse) {
                throw new ErrorResponseException(null, $mgpgResponse->getErrorMessage());
            }

            $purchaseProcess = new PurchaseProcess(
                $mgpgResponse->purchaseProcess,
                $command
            );

            $dto = $this->dtoAssembler->assemble(
                $purchaseProcess,
                app(ConfigService::class)->getSite(
                    $mgpgResponse->purchaseProcess->invoice->charges[0]->siteId
                )
            );

            $this->mgpgToNgService->queuePostback($purchaseProcess, $dto, $command->getPostbackUrl());

            return response(
                $this->successRedirectToClient($dto->jsonSerialize()),
                Response::HTTP_OK
            );
        } catch (MissingParesAndMdException $ex) {
            return response(
                $this->errorRedirectToClient(
                    $this->buildResponse($ex),
                    $ex->returnUrl()
                ),
                Response::HTTP_BAD_REQUEST
            );
        } catch (\Throwable $e) {
            Log::logException($e);
            return $this->error($e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param array $purchaseProcess Result of Purchase process retrieved from 3DS Complete form <input> payload value
     * @return View
     */
    private function successRedirectToClient(array $purchaseProcess): View
    {
        $redirectUrl = $this->extractClientReturnUrl($purchaseProcess['redirectUrl']);
        unset($purchaseProcess['redirectUrl']);

        return view('threeD.complete', [
            'clientUrl' => $redirectUrl,
            'response'  => $purchaseProcess
        ]);
    }

    /**
     * @param string $redirectUrl Provided On MGPG Purchase Init, has the original client url encoded within jwt
     * @return string ClientRedirectUrl
     */
    private function extractClientReturnUrl(string $redirectUrl): string
    {
        $jwt = $this->getLastPathSegment($redirectUrl);

        $token = (new Parser())->parse($jwt);

        return $this->cryptService->decrypt($token->getClaim('clientUrl'));
    }

    /**
     * @param $url
     * @return string
     */
    private function getLastPathSegment($url): string
    {
        $path        = parse_url($url, PHP_URL_PATH);
        $pathTrimmed = trim($path, '/');
        $pathTokens  = explode('/', $pathTrimmed);

        if (substr($path, -1) === '/') {
            array_pop($pathTokens);
        }

        return end($pathTokens);
    }
}
