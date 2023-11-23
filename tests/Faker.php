<?php

declare(strict_types=1);

namespace Tests;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;

trait Faker
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * Faker initialization
     * @return void
     */
    public function configFaker(): void
    {
        $faker = Factory::create(Factory::DEFAULT_LOCALE);

        // Read more: https://github.com/fzaninotto/Faker#faker-internals-understanding-providers
        $faker->addProvider(new class($faker) extends Base {
            /**
             * Reason: whenever we make a call to a biller, we have to use PCI compliant emails.
             *
             * @param null $userName
             * @param null $domainName
             * @return string
             */
            public function email($userName = null, $domainName = null): string
            {
                return sprintf('%s@%s',
                    $userName ?: $this->generator->userName . '.EPS.mindgeek',
                    $domainName ?: 'EPS.mindgeek.com'
                );
            }
        });

        $this->faker = $faker;
    }
}
