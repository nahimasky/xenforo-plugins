<?php

namespace Sky\ProfilePins\Service;

use Sky\ProfilePins\Options;
use XF\Entity\ProfilePost;
use XF\Entity\User;

class ProfilePostPinPolicy
{
    public function canManage(User $visitor, ProfilePost $profilePost, ?string &$error = null): bool
    {
        if (!Options::enabled() || !$visitor->user_id || $profilePost->message_state !== 'visible')
        {
            $error = 'no_permission';
            return false;
        }

        if ($visitor->user_id !== $profilePost->profile_user_id
            && !$visitor->hasPermission('profilePins', 'manageOtherWalls'))
        {
            $error = 'no_permission';
            return false;
        }

        return true;
    }
}
