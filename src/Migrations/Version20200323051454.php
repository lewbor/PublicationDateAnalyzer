<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200323051454 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE article_dates_oa_agregate (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, journal_id INT NOT NULL, domain_id INT DEFAULT NULL, open_access TINYINT(1) DEFAULT NULL, crossref_published_print DATE DEFAULT NULL, crossref_published_online DATE DEFAULT NULL, publisher_received DATE DEFAULT NULL, publisher_accepted DATE DEFAULT NULL, publisher_available_print DATE DEFAULT NULL, publisher_available_online DATE DEFAULT NULL, UNIQUE INDEX UNIQ_D65A200D7294869C (article_id), INDEX IDX_D65A200D478E8802 (journal_id), INDEX IDX_D65A200D115F0EE5 (domain_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_dates_oa_agregate ADD CONSTRAINT FK_D65A200D7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_dates_oa_agregate ADD CONSTRAINT FK_D65A200D478E8802 FOREIGN KEY (journal_id) REFERENCES journal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE article_dates_oa_agregate ADD CONSTRAINT FK_D65A200D115F0EE5 FOREIGN KEY (domain_id) REFERENCES article_url_domain (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE article_dates_oa_agregate');
        $this->addSql('ALTER TABLE article_crossref_data CHANGE crossref_data crossref_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE article_publisher_data CHANGE publisher_data publisher_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE article_unpaywall_data CHANGE publisher_data publisher_data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE article_web_of_science_data CHANGE web_of_science_data web_of_science_data LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE journal CHANGE crossref_data crossref_data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE journal_analytics CHANGE analytics analytics LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE journal_stat CHANGE article_years article_years LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE median_publication_time median_publication_time LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`, CHANGE wos_publication_types wos_publication_types LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
