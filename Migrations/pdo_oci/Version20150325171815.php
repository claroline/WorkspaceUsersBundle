<?php

namespace Claroline\WorkspaceUsersBundle\Migrations\pdo_oci;

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
                id NUMBER(10) NOT NULL, 
                workspace_id NUMBER(10) NOT NULL, 
                user_id NUMBER(10) NOT NULL, 
                created NUMBER(1) NOT NULL, 
                registration_date TIMESTAMP(0) NOT NULL, 
                PRIMARY KEY(id)
            )
        ");
        $this->addSql("
            DECLARE constraints_Count NUMBER; BEGIN 
            SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count 
            FROM USER_CONSTRAINTS 
            WHERE TABLE_NAME = 'CLARO_WORKSPACE_USER' 
            AND CONSTRAINT_TYPE = 'P'; IF constraints_Count = 0 
            OR constraints_Count = '' THEN EXECUTE IMMEDIATE 'ALTER TABLE CLARO_WORKSPACE_USER ADD CONSTRAINT CLARO_WORKSPACE_USER_AI_PK PRIMARY KEY (ID)'; END IF; END;
        ");
        $this->addSql("
            CREATE SEQUENCE CLARO_WORKSPACE_USER_SEQ START WITH 1 MINVALUE 1 INCREMENT BY 1
        ");
        $this->addSql("
            CREATE TRIGGER CLARO_WORKSPACE_USER_AI_PK BEFORE INSERT ON CLARO_WORKSPACE_USER FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN 
            SELECT CLARO_WORKSPACE_USER_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; IF (
                : NEW.ID IS NULL 
                OR : NEW.ID = 0
            ) THEN 
            SELECT CLARO_WORKSPACE_USER_SEQ.NEXTVAL INTO : NEW.ID 
            FROM DUAL; ELSE 
            SELECT NVL(Last_Number, 0) INTO last_Sequence 
            FROM User_Sequences 
            WHERE Sequence_Name = 'CLARO_WORKSPACE_USER_SEQ'; 
            SELECT : NEW.ID INTO last_InsertID 
            FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP 
            SELECT CLARO_WORKSPACE_USER_SEQ.NEXTVAL INTO last_Sequence 
            FROM DUAL; END LOOP; END IF; END;
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