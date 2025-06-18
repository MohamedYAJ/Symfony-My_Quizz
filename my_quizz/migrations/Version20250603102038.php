<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250603102038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE quiz_attempt (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, categorie_id INT DEFAULT NULL, score INT NOT NULL, attempted_at DATETIME NOT NULL, INDEX IDX_AB6AFC6A76ED395 (user_id), INDEX IDX_AB6AFC6BCF5E72D (categorie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6BCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quiz_attempt
        SQL);
    }
}
