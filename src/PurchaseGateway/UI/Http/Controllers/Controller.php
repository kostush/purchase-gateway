<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Laravel\Lumen\Routing\Controller as BaseController;
use ProBillerNG\PurchaseGateway\Domain\ReturnsNextAction;
use ProBillerNG\PurchaseGateway\Exception;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use ProBillerNG\Logger\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Controller
 * @package ProBillerNG\PurchaseGateway\UI\Http\Controllers
 */
class Controller extends BaseController
{
    /**
     * @param \Throwable $error        Error
     * @param int        $httpResponse Response code
     * @return \Illuminate\Http\JsonResponse
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function error(\Throwable $error, int $httpResponse = HttpResponse::HTTP_BAD_REQUEST): JsonResponse
    {
        Log::logException($error);

        return response()->json(
            $this->buildResponse($error),
            $httpResponse
        );
    }

    /**
     * @param \Throwable $error Error
     * @return \Illuminate\Http\JsonResponse
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function badRequest(\Throwable $error): JsonResponse
    {
        Log::logException($error);

        return response()->json(
            $this->buildResponse($error),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @param \Throwable $error Error
     * @return \Illuminate\Http\JsonResponse
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function internalServerError(\Throwable $error): JsonResponse
    {
        Log::logException($error);

        return response()->json(
            $this->buildResponse($error),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @param \Throwable $error Error
     * @return \Illuminate\Http\JsonResponse
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function notFound(\Throwable $error): JsonResponse
    {
        Log::logException($error);

        return response()->json(
            [
                'error' => $error->getMessage(),
                'code'  => $error->getCode()
            ],
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @param \Exception $ex Exception
     * @return View
     */
    public function errorRequest(\Exception $ex): View
    {
        $params = $this->buildResponse($ex);

        return view('error', ['response' => $params]);
    }

    /**
     * @param array  $response  Response array
     * @param string $returnUrl Return url
     * @return View
     */
    protected function errorRedirectToClient(array $response, string $returnUrl): View
    {
        $params = [
            'clientUrl' => $returnUrl,
            'response'  => $response
        ];

        return view('errorWithRedirect', $params);
    }

    /**
     * @return View
     */
    protected function serverError(): View
    {
        return view('serverError');
    }

    /**
     * @param \Throwable|Exception $error Error
     * @return array
     */
    protected function buildResponse(\Throwable $error): array
    {
        $response = [
            'error' => $error->getMessage(),
            'code'  => $error->getCode()
        ];

        if ($error instanceof ReturnsNextAction) {
            $response['nextAction'] = $error->nextAction();
        }

        return $response;
    }

    /**
     * @param string $initialSessionId
     * @param string $generatedSessionId
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function validateAndLogSessionIdDifferences(string $initialSessionId, string $generatedSessionId): void
    {
        if (strcmp($initialSessionId, $generatedSessionId) !== 0) {
            Log::info('Invalid UUID sessionId provided. A new UUID sessionId was generated instead.', [
                'providedSessionId' => $initialSessionId,
                'newSessionId'      => $generatedSessionId
            ]);
        }
    }
}
