<?php

namespace ProBillerNG\PurchaseGateway\UI\Console\Commands;

use Illuminate\Console\Command;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\ServiceBus\Event;
use ProBillerNG\ServiceBus\ServiceBus;

class SimpleMessageSender extends Command
{
    /**
     * @var ServiceBusFactory
     */
    private $serviceBusFactory;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:sender';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a message to the queue';

    /**
     * SimpleMessageSender constructor.
     *
     * @param ServiceBusFactory $serviceBusFactory ServiceBusFactory
     */
    public function __construct(ServiceBusFactory $serviceBusFactory)
    {
        parent::__construct();
        $this->serviceBusFactory = $serviceBusFactory;
    }

    /**
     * Handles the command
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $serviceBus = $this->serviceBusFactory->make();
            $this->sendMessage($serviceBus);
        } catch (\Exception $e) {
            print_r([$e->getMessage(), $e->getTraceAsString()]);
        }
    }

    /**
     * @param ServiceBus $serviceBus ServiceBus
     * @return void
     */
    public function sendMessage(ServiceBus $serviceBus)
    {
        $eventName = 'My\Event\Name';
        $collect   = [
            ['type' => $eventName, 'random' => mt_rand()],
            ['type' => $eventName, 'random' => mt_rand()],
            ['type' => $eventName, 'random' => mt_rand()],
            ['type' => $eventName, 'random' => mt_rand()]
        ];

        foreach ($collect as $item) {
            $messageEvent = new Event($item);
            $serviceBus->publish($messageEvent);
        }
    }
}
