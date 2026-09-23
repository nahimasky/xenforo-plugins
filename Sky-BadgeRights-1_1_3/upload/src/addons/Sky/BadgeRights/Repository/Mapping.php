<?php

namespace Sky\BadgeRights\Repository;

use XF\Mvc\Entity\Repository;

class Mapping extends Repository
{
    public function findMappingsOrdered()
    {
        return $this->finder('Sky\BadgeRights:Mapping')
            ->with('UserGroup')
            ->order('UserGroup.title');
    }

    public function getModeratorGroupIds(): array
    {
        $ids = [];
        foreach ($this->finder('Sky\BadgeRights:Mapping')->where('is_moderator', 1)->fetch() as $mapping)
        {
            $ids[] = (int)$mapping->group_id;
        }
        return $ids;
    }

    /**
     * Finds user_ids currently tracked (via xf_sky_badge_rights_moderator) as holding
     * this group's moderator status. Unlike getGroupHolders()/FIND_IN_SET, this doesn't
     * depend on the group still being present in xf_user.secondary_group_ids, which
     * matters when a user group itself has just been deleted.
     */
    public function getTrackedUserIdsForGroup(int $groupId): array
    {
        $userIds = [];
        foreach ($this->finder('Sky\BadgeRights:ManagedModerator')->fetch() as $tracker)
        {
            if (in_array($groupId, $tracker->group_ids, false))
            {
                $userIds[] = $tracker->user_id;
            }
        }
        return $userIds;
    }

    public function getGroupHolders(int $groupId): array
    {
        return $this->db()->fetchAllKeyed(
            "SELECT user_id, username
             FROM xf_user
             WHERE FIND_IN_SET(?, secondary_group_ids)
             ORDER BY username",
            'user_id',
            [$groupId]
        );
    }
}
