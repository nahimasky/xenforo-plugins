<?php

namespace Sky\ProfilePins\Template;

use Sky\ProfilePins\Service\ProfilePostPinPolicy;
use XF\Template\Templater;

class Callback
{
    public static function renderProfilePostPinMenuItem($contents, array $params, Templater $templater): string
    {
        $profilePost = $params['profilePost'] ?? $params[0] ?? null;
        $visitor = \XF::visitor();

        if (!($profilePost instanceof \XF\Entity\ProfilePost))
        {
            return '';
        }

        $policy = new ProfilePostPinPolicy();
        if (!$policy->canManage($visitor, $profilePost))
        {
            return '';
        }

        return $templater->renderTemplate('public:sky_profile_pins_profile_post_menu_item', [
            'profilePost' => $profilePost,
            'isPinned' => (bool)$profilePost->sky_profile_pinned
        ]);
    }
}
