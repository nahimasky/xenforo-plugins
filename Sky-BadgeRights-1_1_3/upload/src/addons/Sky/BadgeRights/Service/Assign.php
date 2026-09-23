<?php

namespace Sky\BadgeRights\Service;

use Sky\BadgeRights\Entity\Mapping;
use XF\Entity\User;
use XF\Service\AbstractService;

class Assign extends AbstractService
{
    public function assign(User $user, Mapping $mapping): void
    {
        $groupIds = $user->secondary_group_ids;
        if (!in_array($mapping->group_id, $groupIds, true))
        {
            $groupIds[] = $mapping->group_id;
            $user->set('secondary_group_ids', array_values(array_unique($groupIds)));
            $user->save();

            // secondary_group_ids changed, so XF\Entity\User::_postSave() already
            // calls syncModeratorState() for us — don't run it twice.
            return;
        }

        // Group already held: save() above wouldn't have run, so the auto-sync
        // hook wouldn't have fired either. Sync explicitly to stay safe.
        $this->syncModeratorState($user);
    }

    public function unassign(User $user, Mapping $mapping): void
    {
        $groupIds = $user->secondary_group_ids;
        $key = array_search($mapping->group_id, $groupIds, true);
        if ($key !== false)
        {
            unset($groupIds[$key]);
            $user->set('secondary_group_ids', array_values($groupIds));
            $user->save();
            return;
        }

        $this->syncModeratorState($user);
    }

    /**
     * Synchronizes the moderator row with all currently held moderator mappings.
     * Existing/manual moderator rows are never deleted by this add-on.
     */
    public function syncModeratorState(User $user): void
    {
        $mappingRepo = $this->repository('Sky\BadgeRights:Mapping');
        $moderatorGroupIds = $mappingRepo->getModeratorGroupIds();
        $heldModeratorGroups = array_values(array_intersect(
            array_map('intval', $user->secondary_group_ids),
            $moderatorGroupIds
        ));

        /** @var \Sky\BadgeRights\Entity\ManagedModerator|null $tracker */
        $tracker = $this->em()->find('Sky\BadgeRights:ManagedModerator', $user->user_id);

        if (!$heldModeratorGroups)
        {
            if ($tracker)
            {
                if ($tracker->created_moderator_row)
                {
                    $moderator = $this->em()->find('XF:Moderator', $user->user_id);
                    if ($moderator)
                    {
                        $moderator->delete();
                    }
                }
                $tracker->delete();
            }
            return;
        }

        if (!$tracker)
        {
            $tracker = $this->em()->create('Sky\BadgeRights:ManagedModerator');
            $tracker->user_id = $user->user_id;
            $tracker->created_moderator_row = false;
        }

        $tracker->group_ids = $heldModeratorGroups;

        $moderator = $this->em()->find('XF:Moderator', $user->user_id);
        if (!$moderator)
        {
            $moderator = $this->em()->create('XF:Moderator');
            $moderator->user_id = $user->user_id;
            $moderator->is_super_moderator = false;
            $moderator->save();
            $tracker->created_moderator_row = true;
        }

        $tracker->save();
    }

    /**
     * Removes one mapping from all tracker records and cleans up only moderator
     * rows that this add-on originally created.
     */
    public function removeMappingFromAllUsers(int $groupId): void
    {
        $finder = $this->finder('XF:User')
            ->whereSql('FIND_IN_SET(?, secondary_group_ids)', [$groupId]);

        foreach ($finder->fetch() as $user)
        {
            $this->syncModeratorState($user);
        }
    }
}
