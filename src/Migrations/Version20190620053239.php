<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190620053239 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, journal_id INT NOT NULL, doi VARCHAR(255) DEFAULT NULL, name LONGTEXT NOT NULL, year INT NOT NULL, authors LONGTEXT DEFAULT NULL, wos_id VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_23A0E666694147A (doi), UNIQUE INDEX UNIQ_23A0E665C0D6F16 (wos_id), INDEX IDX_23A0E66478E8802 (journal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, issn VARCHAR(100) DEFAULT NULL, eissn VARCHAR(100) DEFAULT NULL, UNIQUE INDEX UNIQ_C1A7E74D5E237E06 (name), UNIQUE INDEX UNIQ_C1A7E74D9FC5D7F6 (issn), UNIQUE INDEX UNIQ_C1A7E74D297107CA (eissn), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E66478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E66478E8802');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE journal');
    }
}
