<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application;

use ProBillerNG\PurchaseGateway\Application\Exceptions\CommandFactoryException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CommandFactoryUnknownCommandException;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\ConsumeEventCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\ServiceBus\CommandFactory;
use ProBillerNG\ServiceBus\Message;

class PurchaseGatewayCommandFactory implements CommandFactory
{
    /**
     * @param Message $message RabbitMQ message
     * @return object Command
     * @throws Exception
     * @throws CommandFactoryException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function createFromMessage(Message $message): object
    {
        try {
            switch ($message->type()) {
                case PurchaseProcessed::class:
                    return ConsumeEventCommand::create(
                        json_encode($message->body())
                    );
                default:
                    throw new CommandFactoryUnknownCommandException($message->type());
            }
        } catch (Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new CommandFactoryException($message->type());
        }
    }
}
