<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers\Mgpg;

use Illuminate\Http\JsonResponse;
use OutOfBoundsException;
use ProbillerMGPG\ClientApi;
use ProbillerMGPG\Common\Response\ErrorResponse;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg\ErrorResponseException;
use ProBillerNG\PurchaseGateway\UI\Http\Controllers\Controller;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ValidateCaptchaRequest;
use Symfony\Component\HttpFoundation\Response;

class CaptchaValidationController extends Controller
{
    /**
     * @param ClientApi              $clientApi
     * @param string                 $step
     * @param ValidateCaptchaRequest $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function validateCaptcha(ClientApi $clientApi, string $step, ValidateCaptchaRequest $request)
    {
        try {
            $correlationId = $request->headers->get('X-CORRELATION-ID');
            $mgpgSessionId = $request->decodedToken()->getClaim('X-Mgpg-Session-Id')->getValue();

            Log::info(
                'MGPGCaptchaValidationAdaptor Beginning validate captcha for ' . $step,
                [
                    'correlationId' => $correlationId,
                    'sessionId'     => $request->decodedToken()->getSessionId(),
                    'mgpgSessionId' => $mgpgSessionId
                ]
            );

            $response = $clientApi->validateCaptcha(
                $correlationId,
                $mgpgSessionId
            );

            if ($response instanceof ErrorResponse) {
                Log::error(
                    'MGPGCaptchaValidationAdaptor Encountered error from MGPG API for ' . $step ,
                    ['message' => $response->getErrorMessage()]
                );
                throw new ErrorResponseException(null, $response->getErrorMessage());
            }

            Log::info(
                'MGPGCaptchaValidationAdaptor Finishing validate captcha for ' . $step,
                [
                    'correlationId' => $correlationId,
                    'sessionId'     => $request->decodedToken()->getSessionId(),
                    'mgpgSessionId' => $response->sessionId
                ]
            );

            return response()->json(['Status' => 'Ok'], Response::HTTP_OK);
        } catch (OutOfBoundsException $e) {
            Log::error('MGPGCaptchaValidationAdaptor Unable to retrieve data from jwt token for ' . $step);

            return $this->badRequest($e);
        } catch (\Throwable $e) {
            Log::error(
                'MGPGCaptchaValidationAdaptor Error occurred during validate captcha for ' . $step,
                [
                    'sessionId'     => $request->decodedToken()->getSessionId(),
                    'mgpgSessionId' => $mgpgSessionId ?? null,
                    'message'       => $e->getMessage()
                ]
            );

            return $this->internalServerError($e);
        }
    }
}
