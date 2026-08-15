<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815091507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: users, customers, offers, tasks, drawings and photos.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, surname VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) NOT NULL, mobilenumber VARCHAR(255) DEFAULT NULL, street VARCHAR(255) NOT NULL, housenumber VARCHAR(255) DEFAULT NULL, plz VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_81398E09B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE offer (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, created_by_id INT NOT NULL, comment VARCHAR(255) DEFAULT NULL, textarea VARCHAR(255) DEFAULT NULL, offer_date DATETIME NOT NULL, term_date DATETIME DEFAULT NULL, created DATETIME NOT NULL, INDEX IDX_29D6873E9395C3F3 (customer_id), INDEX IDX_29D6873EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, offer_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, comment VARCHAR(255) DEFAULT NULL, textarea VARCHAR(255) DEFAULT NULL, task_date DATETIME DEFAULT NULL, term_date DATETIME DEFAULT NULL, INDEX IDX_527EDB259395C3F3 (customer_id), UNIQUE INDEX UNIQ_527EDB2553C674EE (offer_id), INDEX IDX_527EDB25B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_draw (id INT AUTO_INCREMENT NOT NULL, task_id INT DEFAULT NULL, offer_id INT DEFAULT NULL, path LONGTEXT DEFAULT NULL, base64_data LONGTEXT DEFAULT NULL, INDEX IDX_34129748DB60186 (task_id), INDEX IDX_341297453C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE task_image (id INT AUTO_INCREMENT NOT NULL, task_id INT DEFAULT NULL, offer_id INT DEFAULT NULL, path LONGTEXT NOT NULL, INDEX IDX_2991F7F8DB60186 (task_id), INDEX IDX_2991F7F53C674EE (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873E9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE offer ADD CONSTRAINT FK_29D6873EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB259395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2553C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task_draw ADD CONSTRAINT FK_34129748DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE task_draw ADD CONSTRAINT FK_341297453C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
        $this->addSql('ALTER TABLE task_image ADD CONSTRAINT FK_2991F7F8DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('ALTER TABLE task_image ADD CONSTRAINT FK_2991F7F53C674EE FOREIGN KEY (offer_id) REFERENCES offer (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09B03A8386');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873E9395C3F3');
        $this->addSql('ALTER TABLE offer DROP FOREIGN KEY FK_29D6873EB03A8386');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB259395C3F3');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2553C674EE');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25B03A8386');
        $this->addSql('ALTER TABLE task_draw DROP FOREIGN KEY FK_34129748DB60186');
        $this->addSql('ALTER TABLE task_draw DROP FOREIGN KEY FK_341297453C674EE');
        $this->addSql('ALTER TABLE task_image DROP FOREIGN KEY FK_2991F7F8DB60186');
        $this->addSql('ALTER TABLE task_image DROP FOREIGN KEY FK_2991F7F53C674EE');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE offer');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_draw');
        $this->addSql('DROP TABLE task_image');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
