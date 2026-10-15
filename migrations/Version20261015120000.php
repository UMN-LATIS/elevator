<?php

declare(strict_types=1);

namespace Elevator\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Version API keys, preserving existing keys as v1 and defaulting new keys to v2';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_keys ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE api_keys ALTER COLUMN version SET DEFAULT 2');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_keys DROP version');
    }
}