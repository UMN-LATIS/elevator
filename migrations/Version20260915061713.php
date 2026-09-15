<?php

declare(strict_types=1);

namespace Elevator\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260915061713 extends AbstractMigration {
    public function getDescription(): string {
        return 'Add edittemplates permission';
    }

    public function up(Schema $schema): void {

        $this->addSql('INSERT INTO "public"."permissions"("name","label","level","createdat","modifiedat","id") VALUES
            (\'edittemplates\',\'Edit Templates\',\'57\',NULL,NULL,11)');
    }

    public function down(Schema $schema): void {
        $this->addSql('DELETE FROM "public"."permissions" WHERE "id" = 11');
    }
}
