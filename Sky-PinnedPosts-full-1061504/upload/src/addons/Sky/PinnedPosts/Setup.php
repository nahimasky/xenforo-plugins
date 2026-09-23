<?php

/**
 * Sky/PinnedPosts
 * @author sky.
 */

namespace Sky\PinnedPosts;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUninstallTrait;
    use StepRunnerUpgradeTrait;

    public function installStep1()
    {
        $this->schemaManager()->createTable('xf_sky_pinnedpost', function (Create $table)
        {
            $table->addColumn('pinned_post_id', 'int')->autoIncrement();
            $table->addColumn('thread_id', 'int');
            $table->addColumn('post_id', 'int');
            $table->addColumn('pinned_by_user_id', 'int');
            $table->addColumn('pin_date', 'int');
            $table->addColumn('position', 'int')->setDefault(0);
            $table->addColumn('pin_label', 'varchar', 60)->setDefault('');

            $table->addPrimaryKey('pinned_post_id');
            $table->addUniqueKey('post_id', 'post_id_unique');
            $table->addKey('thread_id');
        });
    }

    public function uninstallStep1()
    {
        $this->schemaManager()->dropTable('xf_sky_pinnedpost');
    }

    /**
     * Import ACP data on clean installation so option groups, options, phrases
     * and permission definitions are visible without a separate upgrade.
     */
    public function postInstall(array &$stateChanges)
    {
        $this->enqueueAddOnDataImport();
    }

    /**
     * Import _data after an upgrade while the add-on is active. This restores
     * ACP options, phrases and template modifications on existing installs.
     */
    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        $this->enqueueAddOnDataImport();
    }

    protected function enqueueAddOnDataImport(): void
    {
        \XF::app()->jobManager()->enqueueUnique(
            'sky_pinnedposts_addon_data_import',
            'XF:AddOnData',
            ['addon_id' => 'Sky/PinnedPosts']
        );
    }

    /**
     * Подчищаем неудачную попытку регистрации права из версии 1.0.3
     * (осталась только у тех, кто успел проапгрейдиться до неё).
     */
    public function upgrade1000400Step1()
    {
        $db = $this->db();
        $db->delete('xf_permission', 'permission_group_id = ? AND permission_id = ?', ['general', 'sky_pinnedposts_pin']);
        $db->delete('xf_permission_entry', 'permission_group_id = ? AND permission_id = ?', ['general', 'sky_pinnedposts_pin']);
    }

    /**
     * 1.6.0: у закрепления появляется необязательная короткая метка.
     */
    public function upgrade1060000Step1()
    {
        $this->schemaManager()->alterTable('xf_sky_pinnedpost', function (Alter $table)
        {
            $table->addColumn('pin_label', 'varchar', 60)->setDefault('');
        });
    }

    /**
     * 1.6.9: восстанавливаем только отсутствующую ACP-группу настроек.
     * Пять options и их связи с группой уже существуют на части установок.
     */
    public function upgrade1060900Step1()
    {
        $db = $this->db();

        if (!$db->fetchOne('SELECT group_id FROM xf_option_group WHERE group_id = ?', 'skyPinnedPosts'))
        {
            $db->insert('xf_option_group', [
                'group_id' => 'skyPinnedPosts',
                'icon' => 'fa-thumbtack',
                'display_order' => 50,
                'debug_only' => 0,
                'advanced' => 0,
                'addon_id' => 'Sky/PinnedPosts'
            ]);
        }
    }

    /**
     * 1.6.13: repair old installations where options were imported without
     * their ACP group. New permission data is imported in postUpgrade.
     */
    public function upgrade1061300Step1()
    {
        $db = $this->db();

        if (!$db->fetchOne('SELECT group_id FROM xf_option_group WHERE group_id = ?', 'skyPinnedPosts'))
        {
            $db->insert('xf_option_group', [
                'group_id' => 'skyPinnedPosts',
                'icon' => 'fa-thumbtack',
                'display_order' => 50,
                'debug_only' => 0,
                'advanced' => 0,
                'addon_id' => 'Sky/PinnedPosts'
            ]);
        }
    }

    /**
     * 1.6.15: первые пять posts темы (position 0–4) больше не могут быть
     * закреплены. Удаляем только obsolete add-on pin metadata, не трогая
     * пользовательские сообщения или XF core data.
     */
    public function upgrade1061503Step1()
    {
        $this->db()->query(
            'DELETE pinned
            FROM xf_sky_pinnedpost AS pinned
            INNER JOIN xf_post AS post ON (post.post_id = pinned.post_id)
            WHERE post.position < ?',
            \Sky\PinnedPosts\Repository\PinnedPostRepo::FIRST_PINNABLE_POST_POSITION
        );
    }
}
