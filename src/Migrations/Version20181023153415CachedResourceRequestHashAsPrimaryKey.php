<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181023153415CachedResourceRequestHashAsPrimaryKey extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_85F2D5ECAE1F4542 ON cached_resource');
        $this->addSql('ALTER TABLE cached_resource DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE cached_resource DROP id');
        $this->addSql('ALTER TABLE cached_resource ADD PRIMARY KEY (request_hash)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cached_resource DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE cached_resource ADD id CHAR(36) NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:guid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_85F2D5ECAE1F4542 ON cached_resource (request_hash)');
        $this->addSql('ALTER TABLE cached_resource ADD PRIMARY KEY (id)');
    }
}
