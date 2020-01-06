<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191029034015 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE journal_stat ADD median_publication_time JSON NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE article CHANGE doi doi VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE article_crossref_data CHANGE crossref_data crossref_data LONGTEXT NOT NULL COLLATE utf8mb4_bin, CHANGE published_print published_print DATE DEFAULT \'NULL\', CHANGE published_online published_online DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_publisher_data CHANGE publisher_data publisher_data LONGTEXT NOT NULL COLLATE utf8mb4_bin, CHANGE publisher_received publisher_received DATE DEFAULT \'NULL\', CHANGE publisher_accepted publisher_accepted DATE DEFAULT \'NULL\', CHANGE publisher_available_print publisher_available_print DATE DEFAULT \'NULL\', CHANGE publisher_available_online publisher_available_online DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_unpaywall_data CHANGE publisher_data publisher_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin, CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_web_of_science_data CHANGE web_of_science_data web_of_science_data LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal CHANGE issn issn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE eissn eissn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE crossref_data crossref_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_analytics CHANGE options options LONGTEXT NOT NULL COLLATE utf8mb4_bin, CHANGE analytics analytics LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_stat DROP median_publication_time, CHANGE article_min_year article_min_year INT DEFAULT NULL, CHANGE article_max_year article_max_year INT DEFAULT NULL, CHANGE article_years article_years LONGTEXT NOT NULL COLLATE utf8mb4_bin, CHANGE wos_publication_types wos_publication_types LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_wos_quartile CHANGE category category VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE unpaywall CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE wos_category CHANGE name name VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
