<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TokenIsExpiredException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\Mgpg\MgpgResponseService;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;

class AuthenticateThreeDController extends Controller
{
    /**
     * @var CryptService
     */
    private $cryptService;

    /**
     * @var MgpgResponseService
     */
    private $mgpgResponseService;

    /**
     * AuthenticateThreeDController constructor.
     *
     * @param CryptService        $cryptService        Used to decrypt content of jwt
     * @param MgpgResponseService $mgpgResponseService MgpgResponse Service
     */
    public function __construct(
        CryptService $cryptService,
        MgpgResponseService $mgpgResponseService
    ) {
        $this->cryptService        = $cryptService;
        $this->mgpgResponseService = $mgpgResponseService;
    }

    /**
     * @param Request $request Request
     * @return \Illuminate\Http\JsonResponse
     * @throws TokenIsExpiredException
     */
    public function authenticatePurchase(Request $request)
    {
        $token = (new Parser())->parse($request->jwt);

        $now = new \DateTimeImmutable();

        if ($token->isExpired($now)) {
            throw new TokenIsExpiredException(new Exception());
        }

        return response($this->successRedirectToBank($token), Response::HTTP_OK);
    }

    /**
     * @param Token $token Parsed JWT Token
     * @return View
     */
    protected function successRedirectToBank(Token $token): View
    {
        return view('threeD.authenticate', [
            'authUrl'   => $this->cryptService->decrypt($token->getClaim('authenticateUrl')),
            'pareq'     => $this->cryptService->decrypt($token->getClaim('paReq')),
            'returnUrl' => $token->getClaim('termUrl')
        ]);
    }
}
