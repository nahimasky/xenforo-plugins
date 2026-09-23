<?php

namespace Sky\BadgeStudio\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\FormAction;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;
use Sky\BadgeStudio\IconLibrary;
use Sky\BadgeStudio\Entity\Badge as BadgeEntity;

class Badge extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('badgeStudio');
    }

    public function actionIndex(): View
    {
        $badges = $this->finder('Sky\BadgeStudio:Badge')
            ->order('created_date', 'DESC')
            ->fetch();

        return $this->view('Sky\BadgeStudio:Badge\Index', 'bs_badge_list', [
            'badges' => $badges,
        ]);
    }

    public function actionAdd(): View
    {
        return $this->badgeAddEdit($this->em()->create('Sky\BadgeStudio:Badge'));
    }

    public function actionEdit(ParameterBag $params): View
    {
        $badge = $this->assertRecordExists('Sky\BadgeStudio:Badge', $params->badge_id);
        return $this->badgeAddEdit($badge);
    }

    protected function badgeAddEdit(\Sky\BadgeStudio\Entity\Badge $badge): View
    {
        return $this->view('Sky\BadgeStudio:Badge\Edit', 'bs_badge_edit', [
            'badge' => $badge,
            'icons' => IconLibrary::getIcons(),
        ]);
    }

    public function actionSave(ParameterBag $params)
    {
        if ($params->badge_id)
        {
            $badge = $this->assertRecordExists('Sky\BadgeStudio:Badge', $params->badge_id);
        }
        else
        {
            $badge = $this->em()->create('Sky\BadgeStudio:Badge');
        }

        $form = $this->badgeSaveProcess($badge);
        $form->run();

        return $this->redirect($this->buildLink('bs-badges'));
    }

    protected function badgeSaveProcess(\Sky\BadgeStudio\Entity\Badge $badge): FormAction
    {
        $input = $this->filter([
            'title' => 'str',
            'icon' => 'str',
            'custom_fa_icon' => 'str',
            'color' => 'str',
        ]);

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $input['color']))
        {
            $input['color'] = '#C81E4D';
        }

        if ($input['icon'] === 'custom')
        {
            $input['custom_fa_icon'] = BadgeEntity::normalizeCustomFaIcon($input['custom_fa_icon']);
            if ($input['custom_fa_icon'] === '')
            {
                throw $this->exception($this->error(\XF::phrase('bs_custom_fa_icon_invalid')));
            }
        }
        else
        {
            if (!array_key_exists($input['icon'], IconLibrary::getIcons()))
            {
                $input['icon'] = 'star';
            }

            $input['custom_fa_icon'] = '';
        }

        $form = $this->formAction();
        $form->basicEntitySave($badge, $input);

        return $form;
    }

    public function actionDelete(ParameterBag $params)
    {
        $badge = $this->assertRecordExists('Sky\BadgeStudio:Badge', $params->badge_id);

        if ($this->isPost())
        {
            $badge->delete();
            return $this->redirect($this->buildLink('bs-badges'));
        }

        return $this->view('Sky\BadgeStudio:Badge\Delete', 'bs_badge_delete', [
            'badge' => $badge,
        ]);
    }
}
