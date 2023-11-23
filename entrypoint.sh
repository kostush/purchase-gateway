#!/usr/bin/env bash
set -e

# Ensure we always have .env (initial setup) and .env is always up to date.
# It's intentional that we don't check if .env exists to copy, that way we ensure few things:
# 1. .env is always up to date with features that required .env updates (avoid manual work).
# 2. Changes done on .env will be "discarded" if not applied on .env.example, so we force devs to apply on .env.example
#    beforehand, that way we avoid them to forget changes done on .env directly as they're not tracked (easy to forget).
# 3. Related to #2, usually devs change .env to make something work on their env, like pointing a dependency to another
#    environment, so it works on that dev end, other devs don't know, spend more time trying to fix the same, etc.
#    This way we ensure if something needs to be changed/fixed, it needs to be done on .env.example, so everybody else
#    have it too (as .env.example is tracked).
cp lumen/.env.example lumen/.env

# Ensure that we always have latest dependencies installed.
composer install

touch /var/www/html/lumen/storage/logs/purchase-gateway.log
touch /var/www/html/lumen/storage/logs/purchase-gateway-worker.log
touch /var/www/html/lumen/storage/logs/purchase-gateway-worker-queue.log
chown -R www-data: /var/www/html/lumen/storage/*
chown -R www-data: /var/www/html/lumen/storage/**/*

# Ensure that database is always up to date to develop.
php /var/www/html/lumen/artisan doctrine:migrations:refresh --env=local
php /var/www/html/lumen/artisan doctrine:seed --env=local
