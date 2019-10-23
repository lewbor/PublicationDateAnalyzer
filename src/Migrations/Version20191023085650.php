<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191023085650 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE article_publisher_data (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, publisher_data JSON NOT NULL, UNIQUE INDEX UNIQ_EAD1AFBC7294869C (article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_publisher_data ADD CONSTRAINT FK_EAD1AFBC7294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO article_publisher_data(article_id, publisher_data) select id, publisher_data from article where article.publisher_data is not null');
        $this->addSql('ALTER TABLE article DROP publisher_data');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE article_publisher_data');
        $this->addSql('ALTER TABLE article ADD publisher_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin, CHANGE doi doi VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE published_print published_print DATE DEFAULT \'NULL\', CHANGE published_online published_online DATE DEFAULT \'NULL\', CHANGE publisher_received publisher_received DATE DEFAULT \'NULL\', CHANGE publisher_accepted publisher_accepted DATE DEFAULT \'NULL\', CHANGE publisher_available_print publisher_available_print DATE DEFAULT \'NULL\', CHANGE publisher_available_online publisher_available_online DATE DEFAULT \'NULL\', CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE article_crossref_data CHANGE crossref_data crossref_data LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal CHANGE issn issn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE eissn eissn VARCHAR(100) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE crossref_data crossref_data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_analytics CHANGE analytics analytics LONGTEXT NOT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE journal_stat CHANGE article_min_year article_min_year INT DEFAULT NULL, CHANGE article_max_year article_max_year INT DEFAULT NULL');
        $this->addSql('ALTER TABLE queue_item CHANGE data data LONGTEXT DEFAULT NULL COLLATE utf8mb4_bin');
        $this->addSql('ALTER TABLE unpaywall CHANGE open_access open_access TINYINT(1) DEFAULT \'NULL\'');
    }
}
