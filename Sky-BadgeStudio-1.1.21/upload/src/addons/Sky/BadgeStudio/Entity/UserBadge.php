<?php

namespace Sky\BadgeStudio\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $user_id
 * @property int $badge_id
 * @property int $assigned_by_user_id
 * @property int $assigned_date
 *
 * RELATIONS
 * @property \XF\Entity\User $User
 * @property Badge|null $Badge
 */
class UserBadge extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bs_user_badge';
        $structure->shortName = 'Sky\BadgeStudio:UserBadge';
        $structure->primaryKey = 'user_id';
        $structure->columns = [
            'user_id' => ['type' => self::UINT, 'required' => true],
            'badge_id' => ['type' => self::UINT, 'default' => 0],
            'assigned_by_user_id' => ['type' => self::UINT, 'default' => 0],
            'assigned_date' => ['type' => self::UINT, 'default' => 0],
        ];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ],
            'Badge' => [
                'entity' => 'Sky\BadgeStudio:Badge',
                'type' => self::TO_ONE,
                'conditions' => [['badge_id', '=', '$badge_id']],
            ],
        ];

        return $structure;
    }
}
