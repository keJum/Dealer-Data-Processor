<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210908114853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблиц для хранения информации об автомобилях дилеров';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE car (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE car_attribute (id INT AUTO_INCREMENT NOT NULL, car_id INT NOT NULL, parent_attribute_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL,INDEX IDX_FD981DD7C3C6F69F (car_id), INDEX IDX_FD981DD7E68873D4 (parent_attribute_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE car_attribute ADD CONSTRAINT FK_FD981DD7C3C6F69F FOREIGN KEY (car_id) REFERENCES car (id)');
        $this->addSql('ALTER TABLE car_attribute ADD CONSTRAINT FK_FD981DD7E68873D4 FOREIGN KEY (parent_attribute_id) REFERENCES car_attribute (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car_attribute DROP FOREIGN KEY FK_FD981DD7C3C6F69F');
        $this->addSql('ALTER TABLE car_attribute DROP FOREIGN KEY FK_FD981DD7E68873D4');
        $this->addSql('DROP TABLE car');
        $this->addSql('DROP TABLE car_attribute');
    }
}
