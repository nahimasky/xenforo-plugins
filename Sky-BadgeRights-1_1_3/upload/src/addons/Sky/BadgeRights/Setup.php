<?php

namespace Sky\BadgeRights;

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
        $this->schemaManager()->createTable('xf_sky_badge_rights_map', function (Create $table)
        {
            $table->addColumn('group_id', 'int');
            $table->addColumn('is_moderator', 'tinyint')->setDefault(0);
            $table->addColumn('note', 'varchar', 255)->setDefault('');

            $table->addPrimaryKey('group_id');
        });
    }

    public function installStep2()
    {
        $this->schemaManager()->createTable('xf_sky_badge_rights_moderator', function (Create $table)
        {
            $table->addColumn('user_id', 'int');
            $table->addColumn('group_ids', 'mediumblob');
            $table->addColumn('created_moderator_row', 'tinyint')->setDefault(0);

            $table->addPrimaryKey('user_id');
        });
    }

    public function upgrade1001000Step1()
    {
        // Reconcile existing moderator mappings after upgrading from 1.0.0.
        $repo = $this->app()->repository('Sky\BadgeRights:Mapping');
        $service = $this->app()->service('Sky\BadgeRights:Assign');

        foreach ($repo->findMappingsOrdered()->fetch() as $mapping)
        {
            if (!$mapping->is_moderator)
            {
                continue;
            }

            foreach ($repo->getGroupHolders($mapping->group_id) as $holder)
            {
                $user = $this->app()->em()->find('XF:User', $holder['user_id']);
                if ($user)
                {
                    $service->syncModeratorState($user);
                }
            }
        }
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_sky_badge_rights_map');
    }

    public function uninstallStep2()
    {
        // Remove moderator rows this add-on created before the tracker table
        // (which records which ones those are) is dropped — otherwise they're
        // left behind permanently with no way to identify them afterwards.
        $this->cleanUpCreatedModeratorRows();

        $this->schemaManager()->dropTable('xf_sky_badge_rights_moderator');
    }

    protected function cleanUpCreatedModeratorRows()
    {
        if (!$this->tableExists('xf_sky_badge_rights_moderator'))
        {
            return;
        }

        $rows = $this->app()->db()->fetchAll(
            "SELECT user_id FROM xf_sky_badge_rights_moderator WHERE created_moderator_row = 1"
        );

        foreach ($rows as $row)
        {
            $moderator = $this->app()->em()->find('XF:Moderator', $row['user_id']);
            if ($moderator)
            {
                $moderator->delete();
            }
        }
    }
}
