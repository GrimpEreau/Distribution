<?php

namespace Innova\PathBundle\Migrations\sqlsrv;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2013/09/26 09:17:31
 */
class Version20130926091730 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE innova_step2excludedResource (
                id INT IDENTITY NOT NULL,
                step_id INT,
                resourceNode_id INT,
                PRIMARY KEY (id)
            )
        ");
        $this->addSql("
            CREATE INDEX IDX_4CBCF07C73B21E9C ON innova_step2excludedResource (step_id)
        ");
        $this->addSql("
            CREATE INDEX IDX_4CBCF07CB87FAB32 ON innova_step2excludedResource (resourceNode_id)
        ");
        $this->addSql("
            ALTER TABLE innova_step2excludedResource
            ADD CONSTRAINT FK_4CBCF07C73B21E9C FOREIGN KEY (step_id)
            REFERENCES innova_step (id)
        ");
        $this->addSql("
            ALTER TABLE innova_step2excludedResource
            ADD CONSTRAINT FK_4CBCF07CB87FAB32 FOREIGN KEY (resourceNode_id)
            REFERENCES claro_resource_node (id)
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            DROP TABLE innova_step2excludedResource
        ");
    }
}
