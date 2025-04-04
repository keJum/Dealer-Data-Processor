<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210701100443 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Переименовывание поле token в value в таблице token';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_5F37A13B5F37A13B ON token');
        $this->addSql('ALTER TABLE token CHANGE token value VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F37A13B1D775834 ON token (value)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_5F37A13B1D775834 ON token');
        $this->addSql('ALTER TABLE token CHANGE value token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F37A13B5F37A13B ON token (token)');
    }
}
