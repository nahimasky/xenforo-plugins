<?php

namespace Sky\ComposerBlueprints;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->schemaManager()->createTable('xf_sky_composer_blueprint', function (Create $table)
        {
            $table->addColumn('blueprint_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 100);
            $table->addColumn('description', 'varchar', 255)->setDefault('');
            $table->addColumn('message', 'mediumtext');
            $table->addColumn('contexts', 'mediumblob');
            $table->addColumn('node_ids', 'mediumblob');
            $table->addColumn('display_order', 'int')->setDefault(10);
            $table->addColumn('active', 'tinyint')->setDefault(1);

            $table->addPrimaryKey('blueprint_id');
            $table->addKey(['active', 'display_order']);
        });
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_sky_composer_blueprint');
    }
}
