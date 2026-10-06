<?php

declare(strict_types=1);

namespace Elevator\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add additional settings to instances';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE instances ADD additionalSettings JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE instances DROP additionalsettings');
    }
}