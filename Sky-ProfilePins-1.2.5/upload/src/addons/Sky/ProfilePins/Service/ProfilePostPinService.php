<?php

namespace Sky\ProfilePins\Service;

use Sky\ProfilePins\Exception\ProfilePostPinLimitException;
use Sky\ProfilePins\Options;
use XF\Entity\ProfilePost;
use XF\Entity\User;

class ProfilePostPinService
{
    public function pin(User $profileOwner, ProfilePost $profilePost): void
    {
        if ($profilePost->profile_user_id != $profileOwner->user_id)
        {
            throw new \LogicException('Profile post does not belong to the supplied wall owner.');
        }

        $db = \XF::db();
        $db->beginTransaction();

        try
        {
            $db->fetchOne(
                'SELECT user_id FROM xf_user WHERE user_id = ? FOR UPDATE',
                $profileOwner->user_id
            );

            $maximumPins = Options::maximumPinsPerWall();
            if ($maximumPins === 1)
            {
                $db->update(
                    'xf_profile_post',
                    ['sky_profile_pinned' => 0],
                    'profile_user_id = ? AND profile_post_id <> ?',
                    [$profileOwner->user_id, $profilePost->profile_post_id]
                );
            }
            else
            {
                $pinnedCount = (int)$db->fetchOne(
                    'SELECT COUNT(*) FROM xf_profile_post WHERE profile_user_id = ? AND sky_profile_pinned = 1 AND profile_post_id <> ?',
                    [$profileOwner->user_id, $profilePost->profile_post_id]
                );

                if ($pinnedCount >= $maximumPins)
                {
                    throw new ProfilePostPinLimitException($maximumPins);
                }
            }

            $db->update(
                'xf_profile_post',
                ['sky_profile_pinned' => 1],
                'profile_post_id = ?',
                $profilePost->profile_post_id
            );
            $profilePost->sky_profile_pinned = true;

            $db->commit();
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    public function unpin(ProfilePost $profilePost): void
    {
        \XF::db()->update(
            'xf_profile_post',
            ['sky_profile_pinned' => 0],
            'profile_post_id = ?',
            $profilePost->profile_post_id
        );
        $profilePost->sky_profile_pinned = false;
    }
}
