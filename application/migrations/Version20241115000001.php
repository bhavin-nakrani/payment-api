<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241115000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial payment API schema: users, accounts, and transactions tables';
    }

    public function up(Schema $schema): void
    {
        // Users table
        $this->addSql('CREATE TABLE users (
            id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\',
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            INDEX idx_user_email (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Accounts table
        $this->addSql('CREATE TABLE accounts (
            id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\',
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\',
            account_number VARCHAR(20) NOT NULL,
            balance NUMERIC(19, 4) NOT NULL DEFAULT \'0.0000\',
            currency VARCHAR(3) NOT NULL DEFAULT \'USD\',
            account_type VARCHAR(50) NOT NULL DEFAULT \'CHECKING\',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            version INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_CAC89EAC537048AF (account_number),
            INDEX idx_account_number (account_number),
            INDEX idx_account_user (user_id),
            INDEX IDX_CAC89EACA76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Transactions table
        $this->addSql('CREATE TABLE transactions (
            id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\',
            source_account_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\',
            destination_account_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\',
            reference_number VARCHAR(50) NOT NULL,
            amount NUMERIC(19, 4) NOT NULL,
            currency VARCHAR(3) NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT \'transfer\',
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            description LONGTEXT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            failure_reason LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_EAA81A4C1E4B3E95 (reference_number),
            INDEX idx_transaction_source (source_account_id),
            INDEX idx_transaction_destination (destination_account_id),
            INDEX idx_transaction_status (status),
            INDEX idx_transaction_reference (reference_number),
            INDEX idx_transaction_created (created_at),
            INDEX IDX_EAA81A4C953C1C61 (source_account_id),
            INDEX IDX_EAA81A4CB25B4FEA (destination_account_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign keys
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_CAC89EACA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4C953C1C61 FOREIGN KEY (source_account_id) REFERENCES accounts (id)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CB25B4FEA FOREIGN KEY (destination_account_id) REFERENCES accounts (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_CAC89EACA76ED395');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4C953C1C61');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CB25B4FEA');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE accounts');
        $this->addSql('DROP TABLE users');
    }
}
