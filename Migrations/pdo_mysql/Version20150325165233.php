<?php

namespace Claroline\WorkspaceUsersBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/25 04:52:35
 */
class Version20150325165233 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_workspace_user (
                id INT AUTO_INCREMENT NOT NULL, 
                workspace_id INT NOT NULL, 
                user_id INT NOT NULL, 
                created TINYINT(1) NOT NULL, 
                INDEX IDX_C95C9D5A82D40A1F (workspace_id), 
                INDEX IDX_C95C9D5AA76ED395 (user_id), 
                UNIQUE INDEX workspace_users_unique_workspace_user (user_id, workspace_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ");
        $this->addSql("
            ALTER TABLE claro_workspace_user 
            ADD CONSTRAINT FK_C95C9D5A82D40A1F FOREIGN KEY (workspace_id) 
            REFERENCES claro_workspace (id) 
            ON DELETE CASCADE
        ");
        $this->addSql("
            ALTER TABLE claro_workspace_user 
            ADD CONSTRAINT FK_C95C9D5AA76ED395 FOREIGN KEY (user_id) 
            REFERENCES claro_user (id) 
            ON DELETE CASCADE
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_workspace_user
        ");
    }
}