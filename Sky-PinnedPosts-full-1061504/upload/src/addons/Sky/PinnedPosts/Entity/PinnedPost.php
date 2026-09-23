<?php

/**
 * Sky/PinnedPosts
 * @author sky.
 */

namespace Sky\PinnedPosts\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $pinned_post_id
 * @property int $thread_id
 * @property int $post_id
 * @property int $pinned_by_user_id
 * @property int $pin_date
 * @property int $position
 * @property string $pin_label
 *
 * RELATIONS
 * @property \XF\Entity\Post $Post
 * @property \XF\Entity\Thread $Thread
 * @property \XF\Entity\User $PinnedBy
 */
class PinnedPost extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_sky_pinnedpost';
        $structure->shortName = 'Sky\PinnedPosts:PinnedPost';
        $structure->primaryKey = 'pinned_post_id';

        $structure->columns = [
            'pinned_post_id'    => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'thread_id'         => ['type' => self::UINT, 'required' => true],
            'post_id'           => ['type' => self::UINT, 'required' => true],
            'pinned_by_user_id' => ['type' => self::UINT, 'required' => true],
            'pin_date'          => ['type' => self::UINT, 'default' => \XF::$time],
            'position'          => ['type' => self::UINT, 'default' => 0],
            'pin_label'         => ['type' => self::STR, 'maxLength' => 60, 'default' => ''],
        ];

        $structure->relations = [
            'Post' => [
                'entity'     => 'XF:Post',
                'type'       => self::TO_ONE,
                'conditions' => 'post_id',
                'primary'    => true,
            ],
            'Thread' => [
                'entity'     => 'XF:Thread',
                'type'       => self::TO_ONE,
                'conditions' => 'thread_id',
                'primary'    => true,
            ],
            'PinnedBy' => [
                'entity'     => 'XF:User',
                'type'       => self::TO_ONE,
                'conditions' => [['user_id', '=', '$pinned_by_user_id']],
                'primary'    => true,
            ],
        ];

        return $structure;
    }
}
