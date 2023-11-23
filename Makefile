.PHONY: dockerpull phpcs phpunit artifact

BAMBOO_WORKING_DIRECTORY ?= $(shell pwd)
PHP_VERSION ?= 7.2
PHP_IMAGE ?= harbor.mgcorp.co/probiller/probiller-ng/php
COMPOSER_ARGS ?= --ignore-platform-reqs

dockerpull:
	docker pull composer;
	docker pull $(PHP_IMAGE):$(PHP_VERSION);

## Run all tests (unit tests, code style, linters, etc.)
tests: dockerpull vendor junit phpcs phpunit
	docker run --rm -v "$(BAMBOO_WORKING_DIRECTORY)":/data -w /data $(PHP_IMAGE):$(PHP_VERSION) bash -c 'test `find ./src -iname "*.php" | xargs -n1 -P6 php -l | grep -Fv "No syntax errors" | wc -l` -eq 0'

## Install composer dependencies
vendor: composer.lock
	docker run --rm -v "$(BAMBOO_WORKING_DIRECTORY)":/data -e COMPOSER_CACHE_DIR="/cache/composer" -v /data/composer-cache:/cache/composer -w /data composer install $(COMPOSER_ARGS)

composer.lock:
	composer update --lock $(COMPOSER_ARGS)

## Run the php CodeStyle fixer to DETECT code style violations
phpcs:
	docker run --rm -v "$(BAMBOO_WORKING_DIRECTORY)":/data -w /data $(PHP_IMAGE):$(PHP_VERSION) vendor/bin/phpcs --standard=phpcs.xml --report=junit --report-file=./junit/phpcs.xml

## Run the phpunit from the root phpunit.xml file
phpunit:
	docker run --rm -v "$(BAMBOO_WORKING_DIRECTORY)":/data -w /data $(PHP_IMAGE):$(PHP_VERSION) vendor/bin/phpunit --log-junit ./junit/phpunit.xml --testsuite Unit

## Create junit directory to store test results
junit:
	mkdir -p "$(BAMBOO_WORKING_DIRECTORY)/junit"

## Build an archive for a future deployment (TODO)
artifact:
	pwd