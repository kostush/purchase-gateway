<?php

namespace App\Console;

use Laravel\Lumen\Http\Request;
use ProBillerNG\Projection\UI\Console\Commands\BaseWorkerCommand;
use ProBillerNG\Projection\UI\Console\Commands\ProjectorCommand;
use App\Logger;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use ProBillerNG\PurchaseGateway\UI\Console\Commands\PurchaseToLegacyEnrichedEventConsumer;
use ProBillerNG\PurchaseGateway\UI\Console\Commands\PurchaseToMemberProfileEnrichedEventConsumer;
use ProBillerNG\PurchaseGateway\UI\Console\Commands\RetryFailedEventsPublish;
use ProBillerNG\PurchaseGateway\UI\Console\Commands\SeedCommand;
use ProBillerNG\PurchaseGateway\UI\Console\Commands\SimpleMessageSender;
use Ramsey\Uuid\Uuid;

class Kernel extends ConsoleKernel
{
    use Logger;

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SimpleMessageSender::class,
        BaseWorkerCommand::class,
        ProjectorCommand::class,
        SeedCommand::class,
        PurchaseToLegacyEnrichedEventConsumer::class,
        PurchaseToMemberProfileEnrichedEventConsumer::class,
        RetryFailedEventsPublish::class,
    ];

    /**
     * {@inheritdoc}
     *
     * @param  \Symfony\Component\Console\Input\InputInterface   $input  InputInterface
     * @param  \Symfony\Component\Console\Output\OutputInterface $output OutputInterface
     *
     * @return int
     * @throws \Exception
     */
    public function handle($input, $output = null)
    {
        $request = app(Request::class);
        $request->attributes->set('sessionId', Uuid::uuid4());

        $this->initLogger('APP_WORKER_LOG_FILE', $request);

        return parent::handle($input, $output);
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule Schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
    }
}
