<?php

namespace Sky\BadgeStudio\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Extends XF user entities with the currently assigned BadgeStudio badge.
 *
 * @property \Sky\BadgeStudio\Entity\Badge|null $bs_badge
 */
class User extends XFCP_User
{
    public function getBadgeStudioBadge()
    {
        /** @var \Sky\BadgeStudio\Entity\UserBadge|null $userBadge */
        $userBadge = $this->BadgeStudioUserBadge;

        return $userBadge ? $userBadge->Badge : null;
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->relations['BadgeStudioUserBadge'] = [
            'entity' => 'Sky\BadgeStudio:UserBadge',
            'type' => self::TO_ONE,
            'conditions' => 'user_id',
            'primary' => true,
        ];

        $structure->getters['bs_badge'] = [
            'getter' => 'getBadgeStudioBadge',
            'cache' => false,
        ];

        return $structure;
    }
}
