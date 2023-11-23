<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema as Schema;
use LaravelDoctrine\Migrations\Schema\Builder;
use LaravelDoctrine\Migrations\Schema\Table;

class Version20191119095745 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        (new Builder($schema))->create(
            'sites',
            function (Table $table) {
                $table->increments('id');
                $table->guid('site_id')->setLength(50);
                $table->guid('business_group_id')->setLength(50);
                $table->string('url')->setNotnull(false);
                $table->string('name');
                $table->unique('name');
                $table->string('phone_number')->setNotnull(false);
                $table->string('skype_number')->setNotnull(false);
                $table->string('support_link')->setNotnull(false);
                $table->string('mail_support_link')->setNotnull(false);
                $table->string('message_support_link')->setNotnull(false);
                $table->string('postback_url', 500)->setNotnull(false);
                $table->text('services')->setNotnull(false);
                $table->string('private_key', 36);
                $table->text('public_keys');
                $table->string('descriptor')->setNotnull(false);

                $table->unique(['business_group_id', 'site_id']);
            }
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        (new Builder($schema))->dropIfExists('sites');
    }
}
