<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181023152023RetrieveRequestHashAsPrimaryKey extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_BF893F8AD1B862B8 ON retrieve_request');
        $this->addSql('ALTER TABLE retrieve_request DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE retrieve_request DROP id');
        $this->addSql('ALTER TABLE retrieve_request ADD PRIMARY KEY (hash)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE retrieve_request DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE retrieve_request ADD id CHAR(36) NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:guid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BF893F8AD1B862B8 ON retrieve_request (hash)');
        $this->addSql('ALTER TABLE retrieve_request ADD PRIMARY KEY (id)');
    }
}
