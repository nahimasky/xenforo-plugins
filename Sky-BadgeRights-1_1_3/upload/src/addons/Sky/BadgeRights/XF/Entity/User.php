<?php

namespace Sky\BadgeRights\XF\Entity;

class User extends XFCP_User
{
    protected function _postSave()
    {
        parent::_postSave();

        if (!$this->isChanged('secondary_group_ids'))
        {
            return;
        }

        /** @var \Sky\BadgeRights\Service\Assign $service */
        $service = \XF::service('Sky\BadgeRights:Assign');
        $service->syncModeratorState($this);
    }
}
