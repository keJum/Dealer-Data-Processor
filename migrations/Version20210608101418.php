<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210608101418 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Убрано полее api_token. Сейчас для этого будет использоваться таблицы token';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D6497BA2F5EB ON user');
        $this->addSql('ALTER TABLE user DROP api_token');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD api_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6497BA2F5EB ON user (api_token)');
    }
}
