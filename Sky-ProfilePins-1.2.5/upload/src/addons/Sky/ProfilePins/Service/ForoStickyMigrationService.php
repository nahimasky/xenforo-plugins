<?php

namespace Sky\ProfilePins\Service;

class ForoStickyMigrationService
{
    public function migrate(): int
    {
        $db = \XF::db();
        $legacyRows = $db->fetchAll(
            "
                SELECT profilePost.profile_user_id, profilePost.profile_post_id
                FROM xf_profile_post AS profilePost
                LEFT JOIN xf_profile_post AS existingPin
                    ON (existingPin.profile_user_id = profilePost.profile_user_id
                        AND existingPin.sky_profile_pinned = 1)
                WHERE profilePost.is_sticky = 1
                    AND profilePost.message_state = 'visible'
                    AND existingPin.profile_post_id IS NULL
                ORDER BY profilePost.profile_user_id, profilePost.profile_post_id
            "
        );

        $migratedWallIds = [];
        $migratedCount = 0;

        foreach ($legacyRows AS $legacyRow)
        {
            $profileUserId = (int)$legacyRow['profile_user_id'];
            if (isset($migratedWallIds[$profileUserId]))
            {
                continue;
            }

            $migratedWallIds[$profileUserId] = true;
            $migratedCount += $db->update(
                'xf_profile_post',
                ['sky_profile_pinned' => 1],
                'profile_post_id = ? AND sky_profile_pinned = 0',
                (int)$legacyRow['profile_post_id']
            );
        }

        return $migratedCount;
    }
}
