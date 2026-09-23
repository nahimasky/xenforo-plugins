<?php

namespace Sky\ProfilePins\XF\Entity;

use Sky\ProfilePins\Service\ProfilePostPinPolicy;
use XF\Mvc\Entity\Structure;

class ProfilePost extends XFCP_ProfilePost
{
    protected function _preSave()
    {
        if ($this->message_state !== 'visible' && $this->sky_profile_pinned)
        {
            $this->sky_profile_pinned = false;
        }

        parent::_preSave();
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);
        $structure->columns['sky_profile_pinned'] = ['type' => self::BOOL, 'default' => false];

        return $structure;
    }

    public function canManageSkyProfilePin(): bool
    {
        return (new ProfilePostPinPolicy())->canManage(\XF::visitor(), $this);
    }
}
