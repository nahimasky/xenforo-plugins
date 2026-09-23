<?php

namespace Sky\BadgeRights\Admin\Controller;

use Sky\BadgeRights\Entity\Mapping as MappingEntity;
use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Mapping extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('manageBadgeRights');
    }

    public function actionIndex()
    {
        $mappings = $this->repository('Sky\BadgeRights:Mapping')
            ->findMappingsOrdered()
            ->fetch();

        return $this->view('Sky\BadgeRights:Mapping\Index', 'sbr_mapping_list', [
            'mappings' => $mappings,
        ]);
    }

    public function actionAdd()
    {
        /** @var MappingEntity $mapping */
        $mapping = $this->em()->create('Sky\BadgeRights:Mapping');

        if ($this->isPost())
        {
            return $this->saveMapping($mapping);
        }

        return $this->mappingEditResponse($mapping);
    }

    public function actionEdit(ParameterBag $params)
    {
        /** @var MappingEntity $mapping */
        $mapping = $this->assertRecordExists('Sky\BadgeRights:Mapping', $params->group_id);

        if ($this->isPost())
        {
            return $this->saveMapping($mapping);
        }

        return $this->mappingEditResponse($mapping);
    }

    public function actionDelete(ParameterBag $params)
    {
        /** @var MappingEntity $mapping */
        $mapping = $this->assertRecordExists('Sky\BadgeRights:Mapping', $params->group_id);

        if ($this->isPost())
        {
            $groupId = $mapping->group_id;
            $mapping->delete();

            // Must run after delete() — otherwise getModeratorGroupIds() would
            // still see this mapping and no one would get unsynced.
            /** @var \Sky\BadgeRights\Service\Assign $service */
            $service = $this->service('Sky\BadgeRights:Assign');
            $service->removeMappingFromAllUsers($groupId);

            return $this->redirect($this->buildLink('sky-badge-rights-map'));
        }

        return $this->view('Sky\BadgeRights:Mapping\Delete', 'sbr_mapping_delete', [
            'mapping' => $mapping,
        ]);
    }

    protected function mappingEditResponse(MappingEntity $mapping)
    {
        $groups = $this->finder('XF:UserGroup')->order('title')->fetch();

        if (!$mapping->group_id)
        {
            // only offer groups that aren't already mapped when adding a new one
            $usedGroupIds = $this->finder('Sky\BadgeRights:Mapping')->fetch()->pluck(function ($mapping) {
                return $mapping->group_id;
            })->toArray();
            $groups = $groups->filter(function ($group) use ($usedGroupIds) {
                return !in_array($group->user_group_id, $usedGroupIds, true);
            });
        }

        return $this->view('Sky\BadgeRights:Mapping\Edit', 'sbr_mapping_edit', [
            'mapping' => $mapping,
            'groups' => $groups,
        ]);
    }

    protected function saveMapping(MappingEntity $mapping)
    {
        $this->assertPostOnly();

        $isNew = !$mapping->group_id;

        $groupId = $isNew ? $this->filter('group_id', 'uint') : $mapping->group_id;
        $isModerator = $this->filter('is_moderator', 'bool');
        $note = $this->filter('note', 'str');

        if (!$groupId)
        {
            return $this->error(\XF::phrase('please_complete_required_fields'));
        }

        if ($isNew)
        {
            $mapping->group_id = $groupId;
        }

        $wasModerator = (bool)$mapping->is_moderator;
        $mapping->is_moderator = $isModerator;
        $mapping->note = $note;
        $mapping->save();

        // If the setting changed, immediately synchronize all current holders.
        if (!$isNew && $wasModerator !== $isModerator)
        {
            /** @var \Sky\BadgeRights\Service\Assign $service */
            $service = $this->service('Sky\BadgeRights:Assign');
            $service->removeMappingFromAllUsers($mapping->group_id);
        }

        return $this->redirect($this->buildLink('sky-badge-rights-map'));
    }
}
