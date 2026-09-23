<?php

namespace Sky\BadgeRights\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Assign extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('manageBadgeRights');
    }

    public function actionIndex()
    {
        /** @var \Sky\BadgeRights\Repository\Mapping $mappingRepo */
        $mappingRepo = $this->repository('Sky\BadgeRights:Mapping');
        $mappings = $mappingRepo->findMappingsOrdered()->fetch();

        $rows = [];
        foreach ($mappings as $mapping)
        {
            $rows[] = [
                'mapping' => $mapping,
                'holders' => $mappingRepo->getGroupHolders($mapping->group_id),
            ];
        }

        return $this->view('Sky\BadgeRights:Assign\Index', 'sbr_assign_index', [
            'mappings' => $mappings,
            'rows' => $rows,
        ]);
    }

    public function actionAssign()
    {
        $this->assertPostOnly();

        $username = $this->filter('username', 'str');
        $groupId = $this->filter('group_id', 'uint');

        $user = $this->em()->findOne('XF:User', ['username' => $username]);
        if (!$user)
        {
            return $this->error(\XF::phrase('requested_user_not_found'));
        }

        /** @var \Sky\BadgeRights\Entity\Mapping|null $mapping */
        $mapping = $this->em()->find('Sky\BadgeRights:Mapping', $groupId);
        if (!$mapping)
        {
            return $this->error(\XF::phrase('requested_page_not_found'));
        }

        /** @var \Sky\BadgeRights\Service\Assign $service */
        $service = $this->service('Sky\BadgeRights:Assign');
        $service->assign($user, $mapping);

        return $this->redirect($this->buildLink('sky-badge-rights'));
    }

    public function actionUnassign()
    {
        $this->assertPostOnly();

        $userId = $this->filter('user_id', 'uint');
        $groupId = $this->filter('group_id', 'uint');

        /** @var \XF\Entity\User|null $user */
        $user = $this->em()->find('XF:User', $userId);

        /** @var \Sky\BadgeRights\Entity\Mapping|null $mapping */
        $mapping = $this->em()->find('Sky\BadgeRights:Mapping', $groupId);

        if ($user && $mapping)
        {
            /** @var \Sky\BadgeRights\Service\Assign $service */
            $service = $this->service('Sky\BadgeRights:Assign');
            $service->unassign($user, $mapping);
        }

        return $this->redirect($this->buildLink('sky-badge-rights'));
    }
}
