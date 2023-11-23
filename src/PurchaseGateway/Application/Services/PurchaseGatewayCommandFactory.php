<?php
namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\ServiceBus\CommandFactory;
use ProBillerNG\ServiceBus\Message;

class PurchaseGatewayCommandFactory implements CommandFactory
{
    /**
     * @param Message $message Message
     *
     * @return object
     * @throws Exception
     */
    public function createFromMessage(Message $message): object
    {
        throw new Exception(sprintf("%s can't be converted to a command.", $message->type()));
    }
}
