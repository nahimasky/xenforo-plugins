<?php

namespace Sky\ComposerBlueprints\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Blueprint extends Entity
{
    public const CONTEXT_NEW_THREAD = 'new_thread';
    public const CONTEXT_THREAD_REPLY = 'thread_reply';

    public const VALID_CONTEXTS = [
        self::CONTEXT_NEW_THREAD,
        self::CONTEXT_THREAD_REPLY,
    ];

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_sky_composer_blueprint';
        $structure->shortName = 'Sky\ComposerBlueprints:Blueprint';
        $structure->primaryKey = 'blueprint_id';
        $structure->columns = [
            'blueprint_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
            'description' => ['type' => self::STR, 'maxLength' => 255, 'default' => ''],
            'message' => ['type' => self::STR, 'required' => true],
            'contexts' => ['type' => self::JSON_ARRAY, 'required' => true],
            'node_ids' => ['type' => self::JSON_ARRAY, 'default' => []],
            'display_order' => ['type' => self::UINT, 'default' => 10],
            'active' => ['type' => self::BOOL, 'default' => true],
        ];

        return $structure;
    }
}
