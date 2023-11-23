<?php

declare(strict_types=1);

namespace Tests;

use Dotenv\Dotenv;
use ProBillerNG\SecretManager\Config as SecretManagerConfig;
use ProBillerNG\SecretManager\Exception\SecretManagerConfigFormatException;
use ProBillerNG\SecretManager\Exception\SecretManagerConnectionException;
use ProBillerNG\SecretManager\Exception\SecretManagerRetrieveException;

trait LoadEnv
{
    /**
     * @return void
     * @throws SecretManagerConnectionException|SecretManagerRetrieveException|SecretManagerConfigFormatException
     */
    private static function loadTestEnv(): void
    {
        $ngTestsSecrets = [
            'mgpg_grpc_site_api_tests',
            'mgpg_grpc_rocketgate_tests',
            'mgpg_grpc_netbilling_tests',
            'mgpg_grpc_qysso_tests',
            'mgpg_grpc_epoch_tests',
            'jwt_token',
            'tubes_x_api_key'
        ];

        if (getenv('USE_SECRET_MANAGER') == 'true') {
            SecretManagerConfig::setupConfigInstance(getenv('GOOGLE_CLOUD_PROJECT'));

            foreach ($ngTestsSecrets as $secretId) {
                $secrets = SecretManagerConfig::getConfigArray(
                    $secretId,
                    'latest'
                );
                SecretManagerConfig::storeConfigToEnv($secrets);
            }
        }
        else {
            $dotenv = Dotenv::create(base_path(), '.env.testing');
            //Dotenv::create(base_path(), '.env.testing')->overload();
            $dotenv->load();
        }
    }
    protected function getJwtToken(): string
    {
        return $_ENV['JWT'];
    }
}
