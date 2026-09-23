<?php

namespace Sky\BadgeStudio\XF\Repository;

class Post extends XFCP_Post
{
    public function findPostsForThreadView(\XF\Entity\Thread $thread, array $limits = [])
    {
        $finder = parent::findPostsForThreadView($thread, $limits);
        $finder->with('User.BadgeStudioUserBadge.Badge');

        return $finder;
    }

    public function findSpecificPostsForThreadView(\XF\Entity\Thread $thread, array $postIds, array $limits = [])
    {
        $finder = parent::findSpecificPostsForThreadView($thread, $postIds, $limits);
        $finder->with('User.BadgeStudioUserBadge.Badge');

        return $finder;
    }

    public function findNewestPostsInThread(\XF\Entity\Thread $thread, $newerThan, array $limits = [])
    {
        $finder = parent::findNewestPostsInThread($thread, $newerThan, $limits);
        $finder->with('User.BadgeStudioUserBadge.Badge');

        return $finder;
    }
}
