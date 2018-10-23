<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181023124228RenameResourceCachedResource extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cached_resource (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', url LONGTEXT NOT NULL, headers LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', body LONGTEXT NOT NULL, last_stored DATETIME NOT NULL, request_hash VARCHAR(32) NOT NULL, UNIQUE INDEX UNIQ_85F2D5ECAE1F4542 (request_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE resource');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE resource (id CHAR(36) NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:guid)\', url LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, headers LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:array)\', body LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci, request_hash VARCHAR(32) NOT NULL COLLATE utf8mb4_unicode_ci, last_stored DATETIME NOT NULL, UNIQUE INDEX UNIQ_BC91F416AE1F4542 (request_hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE cached_resource');
    }
}
