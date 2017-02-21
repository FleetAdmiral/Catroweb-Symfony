<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170223231524 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_relation (first_user_id INT NOT NULL, second_user_id INT NOT NULL, similarity NUMERIC(4, 3) DEFAULT \'0\' NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_8204A349B4E2BF69 (first_user_id), INDEX IDX_8204A349B02C53F8 (second_user_id), PRIMARY KEY(first_user_id, second_user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE homepage_click_statistics (id INT AUTO_INCREMENT NOT NULL, program_id INT DEFAULT NULL, user_id INT DEFAULT NULL, type LONGTEXT NOT NULL, clicked_at DATETIME NOT NULL, ip LONGTEXT NOT NULL, locale VARCHAR(255) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT \'\', referrer VARCHAR(255) DEFAULT \'\', INDEX IDX_99AECB2F3EB8070A (program_id), INDEX IDX_99AECB2FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_relation ADD CONSTRAINT FK_8204A349B4E2BF69 FOREIGN KEY (first_user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE user_relation ADD CONSTRAINT FK_8204A349B02C53F8 FOREIGN KEY (second_user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE homepage_click_statistics ADD CONSTRAINT FK_99AECB2F3EB8070A FOREIGN KEY (program_id) REFERENCES program (id)');
        $this->addSql('ALTER TABLE homepage_click_statistics ADD CONSTRAINT FK_99AECB2FA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_relation');
        $this->addSql('DROP TABLE homepage_click_statistics');
    }
}
