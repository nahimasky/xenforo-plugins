<?php

namespace Sky\BadgeRights\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $user_id
 * @property int[] $group_ids
 * @property bool $created_moderator_row
 */
class ManagedModerator extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_sky_badge_rights_moderator';
        $structure->shortName = 'Sky\BadgeRights:ManagedModerator';
        $structure->primaryKey = 'user_id';
        $structure->columns = [
            'user_id' => ['type' => self::UINT, 'required' => true],
            'group_ids' => ['type' => self::JSON_ARRAY, 'default' => []],
            'created_moderator_row' => ['type' => self::BOOL, 'default' => false],
        ];

        return $structure;
    }
}
