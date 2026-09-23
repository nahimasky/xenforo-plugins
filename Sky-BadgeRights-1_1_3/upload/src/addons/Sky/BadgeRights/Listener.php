<?php

namespace Sky\BadgeRights;

use XF\Entity\UserGroup;
use XF\Mvc\Entity\Entity;

class Listener
{
    /**
     * Fired via entity_post_delete on XF:UserGroup. If the deleted group had a
     * Sky Badge Rights mapping, remove the now-orphaned mapping row and resync
     * moderator state for anyone the tracker shows as holding it — using the
     * tracker table rather than xf_user.secondary_group_ids, since that column
     * may or may not have been cleaned up yet by this point.
     */
    public static function userGroupPostDelete(Entity $entity): void
    {
        if (!($entity instanceof UserGroup))
        {
            return;
        }

        $groupId = $entity->user_group_id;

        /** @var \Sky\BadgeRights\Repository\Mapping $repo */
        $repo = \XF::repository('Sky\BadgeRights:Mapping');

        /** @var \Sky\BadgeRights\Entity\Mapping|null $mapping */
        $mapping = \XF::em()->find('Sky\BadgeRights:Mapping', $groupId);
        $affectedUserIds = $repo->getTrackedUserIdsForGroup($groupId);

        if ($mapping)
        {
            $mapping->delete();
        }

        if (!$affectedUserIds)
        {
            return;
        }

        /** @var \Sky\BadgeRights\Service\Assign $service */
        $service = \XF::service('Sky\BadgeRights:Assign');

        foreach ($affectedUserIds as $userId)
        {
            /** @var \XF\Entity\User|null $user */
            $user = \XF::em()->find('XF:User', $userId);
            if ($user)
            {
                $service->syncModeratorState($user);
            }
        }
    }
}
