<?php

namespace Sky\BadgeStudio\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $log_id
 * @property int $user_id
 * @property int|null $old_badge_id
 * @property int|null $new_badge_id
 * @property int $changed_by_user_id
 * @property int $log_date
 *
 * RELATIONS
 * @property \XF\Entity\User $User
 * @property \XF\Entity\User $ChangedBy
 * @property Badge|null $OldBadge
 * @property Badge|null $NewBadge
 */
class BadgeLog extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bs_badge_log';
        $structure->shortName = 'Sky\BadgeStudio:BadgeLog';
        $structure->primaryKey = 'log_id';
        $structure->columns = [
            'log_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'old_badge_id' => ['type' => self::UINT, 'nullable' => true, 'default' => null],
            'new_badge_id' => ['type' => self::UINT, 'nullable' => true, 'default' => null],
            'changed_by_user_id' => ['type' => self::UINT, 'required' => true],
            'log_date' => ['type' => self::UINT, 'default' => \XF::$time],
        ];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
            ],
            'ChangedBy' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => [['user_id', '=', '$changed_by_user_id']],
            ],
            'OldBadge' => [
                'entity' => 'Sky\BadgeStudio:Badge',
                'type' => self::TO_ONE,
                'conditions' => [['badge_id', '=', '$old_badge_id']],
            ],
            'NewBadge' => [
                'entity' => 'Sky\BadgeStudio:Badge',
                'type' => self::TO_ONE,
                'conditions' => [['badge_id', '=', '$new_badge_id']],
            ],
        ];

        return $structure;
    }
}
