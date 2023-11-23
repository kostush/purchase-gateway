<?php

namespace App;

use ProBillerNG\Logger\Config\FileConfig;
use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Logger as NGLogger;
use Illuminate\Http\Request;

trait Logger
{
    /**
     * @var FileConfig
     */
    private $config;

    /**
     * Bootstrap services.
     *
     * @param string       $fileParam Config param for file name
     * @param Request|null $request   Request
     *
     * @return void
     */
    public function initLogger(string $fileParam, Request $request)
    {
        $config = new FileConfig(storage_path() . '/logs/' . env($fileParam));
        $config->setServiceName(config('app.name'));
        $config->setServiceVersion(config('app.version'));

        $config->setSessionId($request->attributes->get('sessionId'));
        $config->setCorrelationId($request->header('X-CORRELATION-ID', ''));

        $logLevels   = NGLogger::getLevels();
        $configLevel = strtoupper(env('APP_LOG_LEVEL'));
        $config->setLogLevel($logLevels[$configLevel] ?? NGLogger::ERROR);

        Log::setConfig($config);
    }
}
