<?php

namespace Sky\ProfilePins\XF\Pub\Controller;

use Sky\ProfilePins\Exception\ProfilePostPinLimitException;
use Sky\ProfilePins\Service\ProfilePostPinPolicy;
use Sky\ProfilePins\Service\ProfilePostPinService;
use XF\Mvc\ParameterBag;

class ProfilePost extends XFCP_ProfilePost
{
    public function actionSkyPin(ParameterBag $params)
    {
        $this->assertRegistrationRequired();

        $profilePost = $this->assertViewableProfilePost($params->profile_post_id);
        $visitor = \XF::visitor();

        $policy = new ProfilePostPinPolicy();
        if (!$policy->canManage($visitor, $profilePost, $error))
        {
            return $this->noPermission($error);
        }

        if (!$this->isPost())
        {
            return $this->view('Sky\ProfilePins:ProfilePost\Confirm', 'sky_profile_pins_profile_post_confirm', [
                'profilePost' => $profilePost,
                'profileUser' => $profilePost->ProfileUser,
                'isPinned' => (bool)$profilePost->sky_profile_pinned
            ]);
        }

        $this->assertPostOnly();

        $service = new ProfilePostPinService();
        if ($profilePost->sky_profile_pinned)
        {
            $service->unpin($profilePost);
            $message = \XF::phrase('sky_profile_pins_unpinned');
        }
        else
        {
            try
            {
                $service->pin($profilePost->ProfileUser, $profilePost);
            }
            catch (ProfilePostPinLimitException $e)
            {
                return $this->error(\XF::phrase('sky_profile_pins_limit_reached', [
                    'maximum' => $e->getMaximumPins()
                ]));
            }

            $message = \XF::phrase('sky_profile_pins_pinned');
        }

        return $this->redirect(
            $this->buildLink('members', $profilePost->ProfileUser)
                . '#profile-post-' . $profilePost->profile_post_id,
            $message
        );
    }
}
