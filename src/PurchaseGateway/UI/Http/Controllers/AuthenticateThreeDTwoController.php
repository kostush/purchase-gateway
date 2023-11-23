<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthenticateThreeDTwoController extends Controller
{
    public function generateAuthUrl(Request $request)
    {
        $stepUpUrl = $request->get('stepUpUrl');
        $stepUpJwt = $request->get('stepUpJwt');
        $transactId = $request->get('transactId');

        return new JsonResponse(
            env('APP_URL') . "/api/v1/purchase/threedtwo/authenticate?stepUpUrl={$stepUpUrl}&stepUpJwt={$stepUpJwt}&transactId={$transactId}"
        );
    }

    /**
     * @param Request $request Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticatePurchase(Request $request)
    {
        $stepUpUrl = $request->get('stepUpUrl');
        $stepUpJwt = $request->get('stepUpJwt');
        $transactId = $request->get('transactId');

        return response(

            "
            <form name=\"stepup\" id=\"stepup\" method=\"post\" action=\"{$stepUpUrl}\">
                <input type=\"hidden\" name=\"JWT\" value=\"{$stepUpJwt}\" />
                <input type=\"hidden\" name=\"MD\" value=\"{$transactId}\" />
            </form>
            <iframe height=\"250\" width=\"400\" name=\"frameId\" id=\"frameId\" >
            </iframe>
            <script>
                window.onload = function () {
                    // Auto submit form on page load
                    console.info(\"about to submit form\");
                    document.getElementById('stepup').submit();
                };
            </script>
            
            ", Response::HTTP_OK);
    }
}
