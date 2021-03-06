<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200127060617 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE article_url_domain (id INT AUTO_INCREMENT NOT NULL, domain VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_55D48F7AA7A91E0B (domain), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_url ADD domain_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE article_url ADD CONSTRAINT FK_666966D4115F0EE5 FOREIGN KEY (domain_id) REFERENCES article_url_domain (id)');
        $this->addSql('CREATE INDEX IDX_666966D4115F0EE5 ON article_url (domain_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE article_url DROP FOREIGN KEY FK_666966D4115F0EE5');
        $this->addSql('DROP TABLE article_url_domain');
        $this->addSql('ALTER TABLE article_crossref_data CHANGE crossref_data crossref_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE article_publisher_data CHANGE publisher_data publisher_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE article_unpaywall_data CHANGE publisher_data publisher_data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('DROP INDEX IDX_666966D4115F0EE5 ON article_url');
        $this->addSql('ALTER TABLE article_url DROP domain_id');
        $this->addSql('ALTER TABLE article_web_of_science_data CHANGE web_of_science_data web_of_science_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE journal CHANGE crossref_data crossref_data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE journal_analytics CHANGE options options LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE analytics analytics LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE journal_stat CHANGE article_years article_years LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE median_publication_time median_publication_time LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE wos_publication_types wos_publication_types LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
