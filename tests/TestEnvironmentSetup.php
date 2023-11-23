<?php
declare(strict_types=1);

namespace Tests;

trait TestEnvironmentSetup
{
    /**
     * @return string
     */
    public function businessGroupTestingXApiKey(): string
    {
        return env('TESTING_SITE_X_API_KEY');
    }

    /**
     * @return string
     */
    public function paysitesXApiKey(): string
    {
        return env('PAYSITES_X_API_KEY');
    }

    /**
     * @return string
     */
    public function tubesXApiKey(): string
    {
        return $_ENV('TUBES_X_API_KEY');
    }
}
