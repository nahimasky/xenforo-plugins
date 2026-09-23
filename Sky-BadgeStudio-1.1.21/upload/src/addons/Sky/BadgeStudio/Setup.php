<?php

namespace Sky\BadgeStudio;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUninstallTrait;
    use StepRunnerUpgradeTrait;

    public function installStep1()
    {
        $this->installOrUpgradeSchema();
    }

    /**
     * Repairs the schema when upgrading a legacy or partially installed copy.
     * XenForo's schema manager skips existing fields and preserves stored data.
     */
    public function upgrade1001090Step1()
    {
        $this->installOrUpgradeSchema();
    }

    public function upgrade1001160Step1()
    {
        \XF::db()->query(
            'DELETE user_badge
            FROM xf_bs_user_badge AS user_badge
            LEFT JOIN xf_bs_badge AS badge ON (badge.badge_id = user_badge.badge_id)
            WHERE user_badge.badge_id <> 0 AND badge.badge_id IS NULL'
        );
    }

    public function upgrade1001190Step1()
    {
        $this->installOrUpgradeSchema();
    }

    public function upgrade1001310Step1()
    {
        \XF::db()->delete(
            'xf_template_modification',
            'addon_id = ? AND modification_key = ?',
            ['Sky/BadgeStudio', 'bs_badge_members_online_inline']
        );
    }

    public function uninstallStep1()
    {
        $schemaManager = $this->schemaManager();

        foreach (array_keys($this->getTables()) AS $tableName)
        {
            $schemaManager->dropTable($tableName);
        }
    }

    protected function installOrUpgradeSchema(): void
    {
        $schemaManager = $this->schemaManager();

        foreach ($this->getTables() AS $tableName => $definition)
        {
            if ($schemaManager->tableExists($tableName))
            {
                $schemaManager->alterTable($tableName, $definition);
            }
            else
            {
                $schemaManager->createTable($tableName, $definition);
            }
        }
    }

    protected function getTables(): array
    {
        return [
            'xf_bs_badge' => function ($table)
            {
                $table->addColumn('badge_id', 'int')->autoIncrement();
                $table->addColumn('title', 'varchar', 50)->setDefault('');
                $table->addColumn('icon', 'varchar', 30)->setDefault('star');
                $table->addColumn('custom_fa_icon', 'varchar', 60)->setDefault('');
                $table->addColumn('color', 'varchar', 7)->setDefault('#C81E4D');
                $table->addColumn('created_date', 'int')->setDefault(0);
                $table->addPrimaryKey('badge_id');
            },
            'xf_bs_user_badge' => function ($table)
            {
                $table->addColumn('user_id', 'int')->setDefault(0);
                $table->addColumn('badge_id', 'int')->setDefault(0);
                $table->addColumn('assigned_by_user_id', 'int')->setDefault(0);
                $table->addColumn('assigned_date', 'int')->setDefault(0);
                $table->addPrimaryKey('user_id');
            },
            'xf_bs_badge_log' => function ($table)
            {
                $table->addColumn('log_id', 'int')->autoIncrement();
                $table->addColumn('user_id', 'int')->setDefault(0);
                $table->addColumn('old_badge_id', 'int')->nullable(true);
                $table->addColumn('new_badge_id', 'int')->nullable(true);
                $table->addColumn('changed_by_user_id', 'int')->setDefault(0);
                $table->addColumn('log_date', 'int')->setDefault(0);
                $table->addPrimaryKey('log_id');
                $table->addKey('user_id');
            },
        ];
    }
}
