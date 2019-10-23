<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191023030927 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE journal_jcr_quartile (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, full_title VARCHAR(255) NOT NULL, issn VARCHAR(9) NOT NULL, category VARCHAR(255) NOT NULL, rank INT NOT NULL, quartile INT NOT NULL, UNIQUE INDEX journal_year_category (issn, year, category), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal_jcr_impact (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, full_title VARCHAR(255) NOT NULL, abbreviated_title VARCHAR(255) NOT NULL, issn VARCHAR(9) NOT NULL, impact_factor DOUBLE PRECISION NOT NULL, impact_factor_5 DOUBLE PRECISION NOT NULL, UNIQUE INDEX journal_year (issn, year), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE journal_jcr_quartile');
        $this->addSql('DROP TABLE journal_jcr_impact');
        $this->addSql('ALTER TABLE article CHANGE doi doi VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE crossref_data crossref_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin, CHANGE publisher_data publisher_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin, CHANGE published_print published_print DATE DEFAULT \'NULL\', CHANGE published_online published_online DATE DEFAULT \'NULL\', CHANGE publisher_received publisher_received DATE DEFAULT \'NULL\', CHANGE publisher_accepted publisher_accepted DATE DEFAULT \'NULL\', CHANGE publisher_available_print publisher_available_print DATE DEFAULT \'NULL\', CHANGE publisher_available_online publisher_available_online DATE DEFAULT \'NULL\', CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE journal CHANGE issn issn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE eissn eissn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE crossref_data crossref_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_analytics CHANGE analytics analytics LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE unpaywall CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
    }
}
