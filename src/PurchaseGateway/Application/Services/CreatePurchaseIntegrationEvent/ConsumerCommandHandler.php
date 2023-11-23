<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\DriverException;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\Base\Application\Services\CommandHandler;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\RepositoryConnectionException;

class ConsumerCommandHandler implements CommandHandler
{
    /**
     * @var CommandHandler
     */
    private $handler;

    /**
     * @param CommandHandler $handler Command Handler
     */
    public function __construct(CommandHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Executes command atomically
     *
     * @param Command $command Command
     *
     * @return mixed
     * @throws RepositoryConnectionException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function execute(Command $command)
    {
        try {
            $result = $this->handler->execute($command);

            return $result;
        } catch (ConnectionException | DriverException $exception) {
            Log::info('Repository connection Exception:' . $exception->getMessage());
            throw new RepositoryConnectionException($exception);
        } catch (\Exception $exception) {
            Log::info('Exception encountered:' . $exception->getMessage());
            if (($exception->getMessage() == "The EntityManager is closed.")
                || ($exception->getPrevious() && $exception->getPrevious()->getMessage() == "The EntityManager is closed.")
                || (strpos($exception->getMessage(), 'Error while sending QUERY packet') !== false)
                || (strpos($exception->getMessage(), 'MySQL server has gone away') !== false)
            ) {
                Log::info('Repository Exception:' . $exception->getMessage());
                throw new RepositoryConnectionException($exception);
            }

            // Bubble
            throw $exception;
        }
    }
}
