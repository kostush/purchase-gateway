<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use ProBillerNG\PurchaseGateway\Application\Services\CaptchaValidation\CaptchaValidationCommand;
use ProBillerNG\PurchaseGateway\Application\Services\CaptchaValidation\CaptchaValidationCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ValidateCaptchaRequest;
use Symfony\Component\HttpFoundation\Response;

class CaptchaValidationController extends Controller
{
    /**
     * @var TransactionalCommandHandler
     */
    private $commandHandler;

    /**
     * ProcessPurchaseController constructor.
     *
     * @param CaptchaValidationCommandHandler $commandHandler Handler
     * @param TransactionalSession            $session        Session
     */
    public function __construct(CaptchaValidationCommandHandler $commandHandler, TransactionalSession $session)
    {
        $this->commandHandler = new TransactionalCommandHandler($commandHandler, $session);
    }

    /**
     * @param string                 $step    Step to validate captcha on
     * @param ValidateCaptchaRequest $request Request
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function validateCaptcha(string $step, ValidateCaptchaRequest $request)
    {
        try {
            $command = new CaptchaValidationCommand(
                $step,
                $request->bearerToken(),
                $request->decodedToken()->getSessionId(),
                $request->siteId()
            );

            $this->commandHandler->execute($command);

            return response()->json(['Status' => 'Ok'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->badRequest($e);
        }
    }
}
