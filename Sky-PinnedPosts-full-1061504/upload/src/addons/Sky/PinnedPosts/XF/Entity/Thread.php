<?php

/**
 * Sky/PinnedPosts
 * @author sky.
 *
 * Class extension: XF\Entity\Thread
 */

namespace Sky\PinnedPosts\XF\Entity;

class Thread extends XFCP_Thread
{
    /** @var array<int, bool>|null */
    protected $skyPinnedPostIds = null;

    /**
     * Доступно в шаблонах как {$thread.canPinPosts()}
     */
    public function canPinPosts(): bool
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->canUserPinInThread($this);
    }

    /**
     * Template-safe check for one concrete post. The thread starter can never
     * be pinned, and the server repeats this validation for direct requests.
     */
    public function canPinPost(\XF\Entity\Post $post): bool
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->canUserPinPost($this, $post);
    }

    public function canUnpinPosts(): bool
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->canUserUnpinInThread($this);
    }

    public function canUnpinPost(\XF\Entity\Post $post): bool
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->canUserUnpinPost($this, $post);
    }

    public function canManagePinnedPosts(): bool
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->canUserManagePinsInThread($this);
    }

    /**
     * Доступно в шаблонах как {$thread.getPinnedPosts()}
     */
    public function getPinnedPosts()
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')
            ->findPinnedPostsForThread($this->thread_id)
            ->fetch();
    }

    /**
     * Return only pins that are not already visible in the uninterrupted reply
     * sequence immediately below the thread starter. This prevents a mixed
     * pin set from duplicating its early replies in the separate pinned block.
     *
     * @param iterable|null $pinnedPosts
     * @return array
     */
    public function getPinnedPostsForBlock($pinnedPosts = null): array
    {
        $pinnedPosts = $pinnedPosts === null ? $this->getPinnedPosts() : $pinnedPosts;
        $pinnedPosts = is_array($pinnedPosts) ? $pinnedPosts : iterator_to_array($pinnedPosts);
        $postsByPosition = [];

        foreach ($pinnedPosts AS $pinnedPost)
        {
            if ($pinnedPost->Post)
            {
                $postsByPosition[(int)$pinnedPost->Post->position] = (int)$pinnedPost->post_id;
            }
        }

        ksort($postsByPosition, SORT_NUMERIC);
        $openingPostIds = [];
        $expectedPosition = 1;

        foreach ($postsByPosition AS $postPosition => $postId)
        {
            if ($postPosition !== $expectedPosition)
            {
                break;
            }

            $openingPostIds[$postId] = true;
            $expectedPosition++;
        }

        return array_values(array_filter($pinnedPosts, static function ($pinnedPost) use ($openingPostIds)
        {
            return !isset($openingPostIds[(int)$pinnedPost->post_id]);
        }));
    }

    /**
     * Template-safe visibility check retained for existing templates.
     *
     * @param iterable|null $pinnedPosts
     */
    public function canShowPinnedPostsBlock($pinnedPosts = null): bool
    {
        return (bool)$this->getPinnedPostsForBlock($pinnedPosts);
    }

    /**
     * Доступно в шаблонах как {$thread.isPostPinned($post.post_id)}.
     */
    public function isPostPinned(int $postId): bool
    {
        if ($this->skyPinnedPostIds === null)
        {
            $this->skyPinnedPostIds = \XF::repository('Sky\\PinnedPosts:PinnedPostRepo')
                ->getPinnedPostIdsForThread($this->thread_id);
        }

        return isset($this->skyPinnedPostIds[$postId]);
    }

    /**
     * Доступно в шаблонах как {$thread.getPinnedPostsLimit()}
     */
    public function getPinnedPostsLimit(): int
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->getMaxPins();
    }

    public function getPinnedPostsLabelLength(): int
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->getPinLabelLength();
    }

    public function canShowPinnedPostMarker(): bool
    {
        return \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->shouldShowOriginalMarker();
    }
}
