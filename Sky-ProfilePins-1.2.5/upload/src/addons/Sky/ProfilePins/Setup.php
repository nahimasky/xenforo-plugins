<?php

namespace Sky\ProfilePins;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1()
    {
        $this->installOrUpgradeNativeProfilePostSchema();
    }

    public function installStep2()
    {
        $this->migrateForoStickyProfilePosts();
    }

    public function upgrade1002000Step1()
    {
        $this->installOrUpgradeNativeProfilePostSchema();
    }

    public function upgrade1002001Step1()
    {
        $this->installOrUpgradeNativeProfilePostSchema();
        $this->migrateForoStickyProfilePosts();
    }

    public function uninstallStep1()
    {
        $schemaManager = $this->schemaManager();

        if ($schemaManager->columnExists('xf_profile_post', 'sky_profile_pinned'))
        {
            $schemaManager->alterTable('xf_profile_post', function(Alter $table)
            {
                $table->dropColumns(['sky_profile_pinned']);
                $table->dropIndexes('sky_profile_user_pinned');
            });
        }
    }

    protected function installOrUpgradeNativeProfilePostSchema(): void
    {
        $schemaManager = $this->schemaManager();
        $tableName = 'xf_profile_post';
        $hasColumn = $schemaManager->columnExists($tableName, 'sky_profile_pinned');
        $indexes = $schemaManager->getTableIndexDefinitions($tableName);
        $hasIndex = isset($indexes['sky_profile_user_pinned']);

        if ($hasColumn && $hasIndex)
        {
            return;
        }

        $schemaManager->alterTable($tableName, function(Alter $table) use ($hasColumn, $hasIndex)
        {
            if (!$hasColumn)
            {
                $table->addColumn('sky_profile_pinned', 'boolean')->setDefault(0);
            }

            if (!$hasIndex)
            {
                $table->addKey(['profile_user_id', 'sky_profile_pinned'], 'sky_profile_user_pinned');
            }
        });
    }

    protected function migrateForoStickyProfilePosts(): void
    {
        if (!$this->schemaManager()->columnExists('xf_profile_post', 'is_sticky'))
        {
            return;
        }

        (new \Sky\ProfilePins\Service\ForoStickyMigrationService())->migrate();
    }
}
