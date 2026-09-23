<?php

/**
 * Sky/PinnedPosts
 *
 * Repository for thread-scoped pinned posts.
 */

namespace Sky\PinnedPosts\Repository;

use XF\Entity\Post;
use XF\Entity\Thread;
use XF\Mvc\Entity\Repository;

class PinnedPostRepo extends Repository
{
    public const MAX_PINNED_POSTS = 3;
    public const MAX_PIN_LABEL_LENGTH = 40;
    public const FIRST_PINNABLE_POST_POSITION = 5;

    /** @var array<int, array<int, bool>> */
    protected $pinnedPostIdCache = [];

    /**
     * Finds displayable pins only. A permanently deleted post is excluded through
     * an INNER JOIN; moderated and soft-deleted posts are excluded by state.
     */
    public function findPinnedPostsForThread(int $threadId)
    {
        return $this->finder('Sky\PinnedPosts:PinnedPost')
            ->where('thread_id', $threadId)
            ->with('Post', true)
            ->with('Post.User')
            ->with('PinnedBy')
            ->where('Post.message_state', 'visible')
            ->order(['position', 'pin_date']);
    }

    /**
     * A request-local cache prevents one database lookup for every action bar.
     *
     * @return array<int, bool>
     */
    public function getPinnedPostIdsForThread(int $threadId): array
    {
        if (!array_key_exists($threadId, $this->pinnedPostIdCache))
        {
            $ids = [];
            $pins = $this->findPinnedPostsForThread($threadId)->fetch();

            foreach ($pins AS $pin)
            {
                $ids[(int)$pin->post_id] = true;
            }

            $this->pinnedPostIdCache[$threadId] = $ids;
        }

        return $this->pinnedPostIdCache[$threadId];
    }

    public function isPostPinnedInThread(int $postId, int $threadId): bool
    {
        $ids = $this->getPinnedPostIdsForThread($threadId);

        return isset($ids[$postId]);
    }

    public function canUserPinInThread(Thread $thread, \XF\Entity\User $user = null): bool
    {
        if (!$this->isThreadEnabled($thread))
        {
            return false;
        }

        $user = $user ?: \XF::visitor();

        if (!$user->user_id)
        {
            return false;
        }

        return $user->hasPermission('skyPinnedPosts', 'skyPinnedPostsPin');
    }

    public function canUserUnpinInThread(Thread $thread, \XF\Entity\User $user = null): bool
    {
        return $this->canUserManagePinsInThread($thread, $user);
    }

    public function canUserManagePinsInThread(Thread $thread, \XF\Entity\User $user = null): bool
    {
        if (!$this->isThreadEnabled($thread))
        {
            return false;
        }

        $user = $user ?: \XF::visitor();

        return $user->user_id && $user->hasPermission('skyPinnedPosts', 'skyPinnedPostsUnpin');
    }

    public function canUserPinPost(Thread $thread, Post $post, \XF\Entity\User $user = null): bool
    {
        if (
            $post->thread_id != $thread->thread_id
            || $post->isFirstPost()
            || (int)$post->position < self::FIRST_PINNABLE_POST_POSITION
            || $post->message_state != 'visible'
        )
        {
            return false;
        }

        $user = $user ?: \XF::visitor();

        return $this->canUserPinInThread($thread, $user)
            || $this->isUserThreadOwner($thread, $user);
    }

    public function canUserUnpinPost(Thread $thread, Post $post, \XF\Entity\User $user = null): bool
    {
        if (
            $post->thread_id != $thread->thread_id
            || $post->isFirstPost()
            || $post->message_state != 'visible'
        )
        {
            return false;
        }

        $user = $user ?: \XF::visitor();

        return $this->canUserManagePinsInThread($thread, $user)
            || $this->isUserThreadOwner($thread, $user);
    }

    /**
     * @return \Sky\PinnedPosts\Entity\PinnedPost|null|false
     * null: already pinned; false: limit reached; Entity: pin created.
     */
    public function pinPost(Thread $thread, Post $post, \XF\Entity\User $pinnedBy, string $pinLabel = '')
    {
        if (!$this->canUserPinPost($thread, $post, $pinnedBy))
        {
            return false;
        }

        if ($this->isPostPinnedInThread($post->post_id, $thread->thread_id))
        {
            return null;
        }

        if ($this->findPinnedPostsForThread($thread->thread_id)->total() >= $this->getMaxPins())
        {
            return false;
        }

        /** @var \Sky\PinnedPosts\Entity\PinnedPost $pinned */
        $pinned = \XF::em()->create('Sky\PinnedPosts:PinnedPost');
        $pinned->thread_id = $thread->thread_id;
        $pinned->post_id = $post->post_id;
        $pinned->pinned_by_user_id = $pinnedBy->user_id;
        $pinned->pin_date = \XF::$time;
        $pinned->position = $this->getNextPosition($thread->thread_id);
        $pinned->pin_label = $this->cleanPinLabel($pinLabel);
        $pinned->save();

        $this->pinnedPostIdCache[$thread->thread_id][$post->post_id] = true;
        $this->sendPinnedAlert($post, $thread, $pinnedBy);

        return $pinned;
    }

    public function getMaxPins(): int
    {
        $limit = (int)$this->getOption('skyPinnedPostsMaxPins', self::MAX_PINNED_POSTS);

        return ($limit >= 1 && $limit <= 20) ? $limit : self::MAX_PINNED_POSTS;
    }

    public function getPinLabelLength(): int
    {
        $length = (int)$this->getOption('skyPinnedPostsLabelLength', self::MAX_PIN_LABEL_LENGTH);

        return ($length >= 10 && $length <= 60) ? $length : self::MAX_PIN_LABEL_LENGTH;
    }

    public function shouldSendAlerts(): bool
    {
        return (bool)$this->getOption('skyPinnedPostsEnableAlerts', true);
    }

    public function shouldShowOriginalMarker(): bool
    {
        return (bool)$this->getOption('skyPinnedPostsShowOriginalMarker', true);
    }

    public function isThreadEnabled(Thread $thread): bool
    {
        if (!(bool)$this->getOption('skyPinnedPostsEnabled', true))
        {
            return false;
        }

        $rawNodeIds = trim((string)$this->getOption('skyPinnedPostsEnabledNodeIds', ''));

        if ($rawNodeIds === '')
        {
            return true;
        }

        $nodeIds = preg_split('/\s*,\s*/', $rawNodeIds, -1, PREG_SPLIT_NO_EMPTY);

        return in_array((string)$thread->node_id, $nodeIds, true);
    }

    /**
     * Return the configured value when it exists; otherwise use the value
     * documented in _data/options.xml. This protects upgrades where an option
     * row has not yet been imported into XenForo's option cache.
     *
     * @param mixed $default
     * @return mixed
     */
    protected function getOption(string $optionId, $default)
    {
        $options = \XF::options();

        return isset($options[$optionId]) ? $options[$optionId] : $default;
    }

    /**
     * Thread authors may pin or unpin any eligible reply in their own thread.
     * Managing pin order remains restricted to the explicit ACP permission.
     */
    protected function isUserThreadOwner(Thread $thread, \XF\Entity\User $user): bool
    {
        return $user->user_id
            && (int)$thread->user_id === (int)$user->user_id;
    }

    protected function cleanPinLabel(string $pinLabel): string
    {
        $pinLabel = trim(preg_replace('/\s+/u', ' ', $pinLabel));

        return \XF::app()->stringFormatter()->wholeWordTrim($pinLabel, $this->getPinLabelLength(), '');
    }

    protected function getNextPosition(int $threadId): int
    {
        $lastPin = $this->finder('Sky\PinnedPosts:PinnedPost')
            ->where('thread_id', $threadId)
            ->order('position', 'DESC')
            ->fetchOne();

        return $lastPin ? ((int)$lastPin->position + 1) : 0;
    }

    protected function sendPinnedAlert(Post $post, Thread $thread, \XF\Entity\User $pinnedBy): void
    {
        if (!$this->shouldSendAlerts() || !$post->User || $post->User->user_id == $pinnedBy->user_id)
        {
            return;
        }

        $this->repository('XF:UserAlert')->alert(
            $post->User,
            $pinnedBy->user_id,
            $pinnedBy->username,
            'user',
            $post->User->user_id,
            'sky_pinnedposts_pinned',
            [
                'thread_id' => $thread->thread_id,
                'thread_title' => $thread->title,
                'post_id' => $post->post_id,
            ],
            [
                'depends_on_addon_id' => 'Sky/PinnedPosts',
            ]
        );
    }

    public function unpinPost(int $postId, int $threadId = null): bool
    {
        $finder = $this->finder('Sky\PinnedPosts:PinnedPost')->where('post_id', $postId);

        if ($threadId)
        {
            $finder->where('thread_id', $threadId);
        }

        $pinned = $finder->fetchOne();

        if (!$pinned)
        {
            return false;
        }

        $pinned->delete();

        if ($threadId && isset($this->pinnedPostIdCache[$threadId]))
        {
            unset($this->pinnedPostIdCache[$threadId][$postId]);
        }

        return true;
    }

    /**
     * Removes all pin records associated with a post. The post entity extension
     * calls this when a post is hidden or permanently deleted.
     */
    public function deletePinsForPost(int $postId): void
    {
        $pins = $this->finder('Sky\PinnedPosts:PinnedPost')
            ->where('post_id', $postId)
            ->fetch();

        foreach ($pins AS $pin)
        {
            $threadId = (int)$pin->thread_id;
            $pin->delete();

            if (isset($this->pinnedPostIdCache[$threadId]))
            {
                unset($this->pinnedPostIdCache[$threadId][$postId]);
            }
        }
    }

    /**
     * Swaps a pin with its visible neighbour. The caller has already checked
     * that the current visitor is allowed to manage this thread.
     */
    public function movePinnedPost(int $pinnedPostId, string $direction, int $threadId): bool
    {
        /** @var \Sky\PinnedPosts\Entity\PinnedPost|null $pinned */
        $pinned = \XF::em()->find('Sky\PinnedPosts:PinnedPost', $pinnedPostId);

        if (!$pinned || $pinned->thread_id != $threadId)
        {
            return false;
        }

        if (!in_array($direction, ['up', 'down'], true))
        {
            return false;
        }

        $orderedPins = [];
        $index = null;

        foreach ($this->findPinnedPostsForThread($threadId)->fetch() AS $listPin)
        {
            if ((int)$listPin->pinned_post_id === $pinnedPostId)
            {
                $index = count($orderedPins);
            }

            $orderedPins[] = $listPin;
        }

        if ($index === null)
        {
            return false;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swapIndex < 0 || $swapIndex >= count($orderedPins))
        {
            return false;
        }

        /** @var \Sky\PinnedPosts\Entity\PinnedPost $other */
        $other = $orderedPins[$swapIndex];
        $position = $pinned->position;
        $pinned->position = $other->position;
        $other->position = $position;

        $pinned->save();
        $other->save();

        return true;
    }
}
