#!/bin/bash

function check_dependencies ()
{
    if [ ! "$(docker ps -q -f name=pb_core)" ]; then
        echo "####################################################################################"
        echo "###                                                                              ###"
        echo "### WARNING: Billing Gateway project is not running, please setup.               ###"
        echo "### https://stash.mgcorp.co/projects/PROB/repos/billing-gateway/browse/Readme.md ###"
        echo "###                                                                              ###"
        echo "####################################################################################"
        exit 0
    fi

    if [ ! "$(docker ps -q -f name=logger)" ]; then
        echo "#############################################################################"
        echo "###                                                                       ###"
        echo "### WARNING: Logger is not running, please setup.                         ###"
        echo "### https://stash.mgcorp.co/projects/pbngbe/repos/logger/browse/README.md ###"
        echo "###                                                                       ###"
        echo "#############################################################################"
        exit 0
    fi

    if [ ! "$(docker ps -q -f name=ng_fraud_service)" ]; then
        echo "####################################################################################"
        echo "###                                                                              ###"
        echo "### WARNING: Fraud Service is not running, please setup.                         ###"
        echo "### https://stash.mgcorp.co/projects/pbngbe/repos/fraud-service/browse/Readme.md ###"
        echo "###                                                                              ###"
        echo "####################################################################################"
        exit 0
    fi

    if [ ! "$(docker ps -q -f name=ng_bin_routing_service)" ]; then
        echo "##########################################################################################"
        echo "###                                                                                    ###"
        echo "### WARNING: Bin Routing Service is not running, please setup.                         ###"
        echo "### https://stash.mgcorp.co/projects/pbngbe/repos/bin-routing-service/browse/Readme.md ###"
        echo "###                                                                                    ###"
        echo "##########################################################################################"
        exit 0
    fi

    if [ ! "$(docker ps -q -f name=biller-mapping-service.ng)" ]; then
        echo "###################################################################################################"
        echo "###                                                                                             ###"
        echo "### WARNING: Biller Mapping Service is not running, please setup.                               ###"
        echo "### https://stash.mgcorp.co/projects/pbngbe/repos/price-biller-mapping-service/browse/Readme.md ###"
        echo "###                                                                                             ###"
        echo "###################################################################################################"
        exit 0
    fi

    if [ ! "$(docker ps -q -f name=ng_transaction_service)" ]; then
        echo "##########################################################################################"
        echo "###                                                                                    ###"
        echo "### WARNING: Transaction Service is not running, please setup.                         ###"
        echo "### https://stash.mgcorp.co/projects/pbngbe/repos/transaction-service/browse/Readme.md ###"
        echo "###                                                                                    ###"
        echo "##########################################################################################"
        exit 0
    fi
}

function setup_dot_env ()
{
    if [ ! -f lumen/.env ]; then
        if [[ -f lumen/.env.example ]]; then
            cp lumen/.env.example lumen/.env
        else
            echo "#########################################################################################"
            echo "###                                                                                   ###"
            echo "### WARNING: .env.example file does not exist and it's required for the proper setup! ###"
            echo "###                                                                                   ###"
            echo "#########################################################################################"
            exit 0
        fi
    fi
}

function docker_pull_and_up ()
{
    docker-compose pull
    docker-compose up -d
}

function composer_install ()
{
    if [ ! -d "vendor" ]; then
        docker run --rm -v /${PWD}:/app neatous/composer-prestissimo install --ignore-platform-reqs --prefer-dist --no-suggest
    fi
}

function doctrine_migrations ()
{
    sleep 10
    docker-compose exec web php lumen/artisan doctrine:migrations:migrate
}

function fix_logs_permissions ()
{
    docker-compose exec web chown www-data: -R lumen/storage
    docker-compose exec web chmod 744 -R lumen/storage/logs
}

function run ()
{
    check_dependencies
    setup_dot_env
    docker_pull_and_up
    composer_install
    doctrine_migrations
    fix_logs_permissions
}

run
