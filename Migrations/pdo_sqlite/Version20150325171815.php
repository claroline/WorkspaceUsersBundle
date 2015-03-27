<?php

namespace Claroline\WorkspaceUsersBundle\Migrations\pdo_sqlite;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/03/25 05:18:16
 */
class Version20150325171815 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE claro_workspace_user (
                id INTEGER NOT NULL, 
                workspace_id INTEGER NOT NULL, 
                user_id INTEGER NOT NULL, 
                created BOOLEAN NOT NULL, 
                registration_date DATETIME NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_C95C9D5A82D40A1F ON claro_workspace_user (workspace_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_C95C9D5AA76ED395 ON claro_workspace_user (user_id)
        ");
        $this->addSql("
            CREATE UNIQUE INDEX workspace_users_unique_workspace_user ON claro_workspace_user (user_id, workspace_id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE claro_workspace_user
        ");
    }
}