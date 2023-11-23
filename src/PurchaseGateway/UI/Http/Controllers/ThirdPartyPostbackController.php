<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\UI\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\NoBodyOrHeaderReceivedException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\SessionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionNotFoundException;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommand;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\ThirdPartyPostback\ThirdPartyPostbackCommandHandlerFactory;
use ProBillerNG\PurchaseGateway\Application\Services\TransactionalCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Exceptions\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\FailedDependencyException;

class ThirdPartyPostbackController extends Controller
{
    /**
     * @var ThirdPartyPostbackCommandHandler
     */
    protected $commandHandler;

    /**
     * ThirdPartyPostbackController constructor.
     * @param ThirdPartyPostbackCommandHandlerFactory $commandHandlerFactory Command handler factory.
     * @param TransactionalSession                    $session               Session.
     * @param Request                                 $request               Third party postback request.
     */
    public function __construct(
        ThirdPartyPostbackCommandHandlerFactory $commandHandlerFactory,
        TransactionalSession $session,
        Request $request
    ) {
        $postbackType         = $request->input('type');
        $handler              = $commandHandlerFactory->getHandler($postbackType);
        $this->commandHandler = new TransactionalCommandHandler($handler, $session);
    }

    /**
     * @param Request $request   Request
     * @param string  $sessionId Session ID
     *
     * @return JsonResponse
     *
     * @throws Exception
     * @throws InvalidUUIDException
     * @throws NoBodyOrHeaderReceivedException
     */
    public function postback(Request $request, string $sessionId): JsonResponse
    {
        Log::info('Beginning the postback from third party biller.');

        try {
            $command = new ThirdPartyPostbackCommand(
                $sessionId,
                $request->input('payload', []),
                $request->input('type')
            );

            $result = $this->commandHandler->execute($command);

            return response()->json($result);
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
    }
}
