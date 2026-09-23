<?php

namespace Sky\ProfilePins\XF\Pub\Controller;

use XF\Mvc\Entity\ArrayCollection;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class Member extends XFCP_Member
{
    public function actionView(ParameterBag $params)
    {
        $reply = parent::actionView($params);
        if (!($reply instanceof View) || (int)($reply->getParam('page') ?? 1) !== 1)
        {
            return $reply;
        }

        $profileUser = $reply->getParam('user');
        $profilePosts = $reply->getParam('profilePosts');
        if (!$profileUser || !$profilePosts || !$profileUser->canViewPostsOnProfile())
        {
            return $reply;
        }

        $pinnedPosts = $this->em()->getFinder('XF:ProfilePost')
            ->where('profile_user_id', $profileUser->user_id)
            ->where('sky_profile_pinned', 1)
            ->where('message_state', 'visible')
            ->with('full')
            ->order('post_date', 'DESC')
            ->fetch();

        if (!$pinnedPosts->count())
        {
            return $reply;
        }

        $posts = $profilePosts->toArray();
        $orderedPinnedPosts = [];

        foreach ($pinnedPosts AS $pinnedPost)
        {
            if (!$pinnedPost->canView())
            {
                continue;
            }

            unset($posts[$pinnedPost->profile_post_id]);
            $orderedPinnedPosts[$pinnedPost->profile_post_id] = $pinnedPost;
        }

        if ($orderedPinnedPosts)
        {
            $reply->setParam('profilePosts', new ArrayCollection($orderedPinnedPosts + $posts));
        }

        return $reply;
    }
}
