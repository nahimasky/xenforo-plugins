<?php

namespace Sky\BadgeRights\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $group_id
 * @property bool $is_moderator
 * @property string $note
 *
 * RELATIONS
 * @property-read \XF\Entity\UserGroup|null $UserGroup
 */
class Mapping extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_sky_badge_rights_map';
        $structure->shortName = 'Sky\BadgeRights:Mapping';
        $structure->primaryKey = 'group_id';
        $structure->columns = [
            'group_id' => ['type' => self::UINT, 'required' => true],
            'is_moderator' => ['type' => self::BOOL, 'default' => false],
            'note' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
        ];
        $structure->getters = [];
        $structure->relations = [
            'UserGroup' => [
                'entity' => 'XF:UserGroup',
                'type' => self::TO_ONE,
                'conditions' => [['user_group_id', '=', '$group_id']],
                'primary' => true,
            ],
        ];

        return $structure;
    }
}
