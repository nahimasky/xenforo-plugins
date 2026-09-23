<?php

namespace Sky\BadgeStudio\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class UserBadge extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('badgeStudio');
    }

    public function actionIndex(): View
    {
        $page = $this->filterPage();
        $perPage = 50;
        $usernameFilter = $this->filter('username', 'str');

        $finder = $this->finder('XF:User')
            ->where('user_id', '>', 0)
            ->order('username');

        if ($usernameFilter !== '')
        {
            $finder->where('username', 'LIKE', $finder->escapeLike($usernameFilter, '%?%'));
        }

        $total = $finder->total();
        $users = $finder->limitByPage($page, $perPage)->fetch();

        $userBadges = $this->em()->findByIds('Sky\BadgeStudio:UserBadge', $users->keys());
        $badgesByUserId = [];

        foreach ($userBadges AS $userBadge)
        {
            $badgesByUserId[(int)$userBadge->user_id] = $userBadge;
        }

        $rows = [];

        foreach ($users AS $user)
        {
            $rows[] = [
                'user' => $user,
                'userBadge' => $badgesByUserId[(int)$user->user_id] ?? null,
            ];
        }

        return $this->view('Sky\BadgeStudio:UserBadge\Index', 'bs_userbadge_list', [
            'rows' => $rows,
            'usernameFilter' => $usernameFilter,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $user = $this->assertRecordExists('XF:User', $params->user_id, null, 'requested_user_not_found');

        if ($this->isPost())
        {
            return $this->bsSaveBadge($user);
        }

        /** @var \Sky\BadgeStudio\Entity\UserBadge|null $userBadge */
        $userBadge = $this->em()->find('Sky\BadgeStudio:UserBadge', $user->user_id);

        $log = $this->finder('Sky\BadgeStudio:BadgeLog')
            ->where('user_id', $user->user_id)
            ->order('log_date', 'DESC')
            ->fetch();

        $badges = $this->finder('Sky\BadgeStudio:Badge')->order('title')->fetch();

        return $this->view('Sky\BadgeStudio:UserBadge\Edit', 'bs_userbadge_edit', [
            'user' => $user,
            'currentBadgeId' => $userBadge ? $userBadge->badge_id : 0,
            'badges' => $badges,
            'log' => $log,
        ]);
    }

    protected function bsSaveBadge(\XF\Entity\User $user)
    {
        $newBadgeId = $this->filter('badge_id', 'uint');

        if ($newBadgeId)
        {
            $this->assertRecordExists('Sky\BadgeStudio:Badge', $newBadgeId, null, 'requested_badge_not_found');
        }

        /** @var \Sky\BadgeStudio\Entity\UserBadge|null $userBadge */
        $userBadge = $this->em()->find('Sky\BadgeStudio:UserBadge', $user->user_id);
        $oldBadgeId = $userBadge ? $userBadge->badge_id : null;

        if (!$userBadge)
        {
            $userBadge = $this->em()->create('Sky\BadgeStudio:UserBadge');
            $userBadge->user_id = $user->user_id;
        }

        if ($oldBadgeId !== $newBadgeId)
        {
            $userBadge->badge_id = $newBadgeId;
            $userBadge->assigned_by_user_id = \XF::visitor()->user_id;
            $userBadge->assigned_date = \XF::$time;
            $userBadge->save();

            /** @var \Sky\BadgeStudio\Entity\BadgeLog $log */
            $log = $this->em()->create('Sky\BadgeStudio:BadgeLog');
            $log->user_id = $user->user_id;
            $log->old_badge_id = $oldBadgeId ?: null;
            $log->new_badge_id = $newBadgeId ?: null;
            $log->changed_by_user_id = \XF::visitor()->user_id;
            $log->log_date = \XF::$time;
            $log->save();
        }

        return $this->redirect($this->buildLink('bs-user-badges'));
    }
}
