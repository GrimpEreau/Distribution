<?php

namespace Claroline\CoreBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution.
 *
 * Generation date: 2019/05/14 02:59:25
 */
class Version20190514145921 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
            ALTER TABLE claro_resource_node
            ADD slug VARCHAR(128)
        ');

        $this->addSql('
            CREATE UNIQUE INDEX UNIQ_A76799FF989D9B62 ON claro_resource_node (slug)
        ');
    }

    public function down(Schema $schema)
    {
        $this->addSql('
            DROP INDEX UNIQ_A76799FF989D9B62 ON claro_resource_node
        ');

        $this->addSql('
            ALTER TABLE claro_resource_node
            DROP slug
        ');
    }
}
