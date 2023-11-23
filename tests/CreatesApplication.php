<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;
use Laravel\Lumen\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../lumen/bootstrap/app.php';

        $app->make(Kernel::class);

        Hash::setRounds(4);

        return $app;
    }
}
