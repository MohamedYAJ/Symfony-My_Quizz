<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606135750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6BCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt DROP FOREIGN KEY FK_AB6AFC6BCF5E72D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_attempt ADD CONSTRAINT FK_AB6AFC6BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
    }
}
