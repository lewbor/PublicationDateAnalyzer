<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191023093240 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE article_publisher_data ADD publisher_received DATE DEFAULT NULL, ADD publisher_accepted DATE DEFAULT NULL, ADD publisher_available_print DATE DEFAULT NULL, ADD publisher_available_online DATE DEFAULT NULL');

        $this->addSql('update article_publisher_data set publisher_received=(select publisher_received from article where article.id=article_publisher_data.article_id)');
        $this->addSql('update article_publisher_data set publisher_accepted=(select publisher_accepted from article where article.id=article_publisher_data.article_id)');
        $this->addSql('update article_publisher_data set publisher_available_print=(select publisher_available_print from article where article.id=article_publisher_data.article_id)');
        $this->addSql('update article_publisher_data set publisher_available_online=(select publisher_available_online from article where article.id=article_publisher_data.article_id)');


        $this->addSql('ALTER TABLE article DROP publisher_received, DROP publisher_accepted, DROP publisher_available_print, DROP publisher_available_online');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE article ADD publisher_received DATE DEFAULT \'NULL\', ADD publisher_accepted DATE DEFAULT \'NULL\', ADD publisher_available_print DATE DEFAULT \'NULL\', ADD publisher_available_online DATE DEFAULT \'NULL\', CHANGE doi doi VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_crossref_data CHANGE crossref_data crossref_data LONGTEXT NOT NULL COLLATE utf8mb4_bin, CHANGE published_print published_print DATE DEFAULT \'NULL\', CHANGE published_online published_online DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_publisher_data DROP publisher_received, DROP publisher_accepted, DROP publisher_available_print, DROP publisher_available_online, CHANGE publisher_data publisher_data LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal CHANGE issn issn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE eissn eissn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE crossref_data crossref_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_analytics CHANGE analytics analytics LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_stat CHANGE article_min_year article_min_year INT DEFAULT NULL, CHANGE article_max_year article_max_year INT DEFAULT NULL');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE unpaywall CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
    }
}
