<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191023090421 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // $this->addSql('ALTER TABLE article_crossref_data ADD published_print DATE DEFAULT NULL, ADD published_online DATE DEFAULT NULL');
        $this->addSql('update article_crossref_data set published_print=(select published_print from article where article.id=article_crossref_data.article_id)');
        $this->addSql('update article_crossref_data set published_online=(select published_online from article where article.id=article_crossref_data.article_id)');
        $this->addSql('ALTER TABLE article DROP published_print, DROP published_online');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE article ADD published_print DATE DEFAULT \'NULL\', ADD published_online DATE DEFAULT \'NULL\', CHANGE doi doi VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE publisher_received publisher_received DATE DEFAULT \'NULL\', CHANGE publisher_accepted publisher_accepted DATE DEFAULT \'NULL\', CHANGE publisher_available_print publisher_available_print DATE DEFAULT \'NULL\', CHANGE publisher_available_online publisher_available_online DATE DEFAULT \'NULL\', CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_crossref_data DROP published_print, DROP published_online, CHANGE crossref_data crossref_data LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE article_publisher_data CHANGE publisher_data publisher_data LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal CHANGE issn issn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE eissn eissn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE crossref_data crossref_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_analytics CHANGE analytics analytics LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_stat CHANGE article_min_year article_min_year INT DEFAULT NULL, CHANGE article_max_year article_max_year INT DEFAULT NULL');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE unpaywall CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
    }
}
