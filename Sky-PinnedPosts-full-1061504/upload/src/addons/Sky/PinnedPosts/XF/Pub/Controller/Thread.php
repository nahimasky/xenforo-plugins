<?php

/**
 * Sky/PinnedPosts
 *
 * Class extension: XF\Pub\Controller\Thread
 */

namespace Sky\PinnedPosts\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
    public function actionPinPost(ParameterBag $params)
    {
        $thread = $this->assertViewableThread($params->thread_id);
        $repo = $this->getPinnedPostRepo();

        $postId = $this->filter('post_id', 'uint');
        $post = $this->em()->find('XF:Post', $postId);

        if (!$post || $post->thread_id != $thread->thread_id)
        {
            return $this->notFound();
        }

        if (!$repo->canUserPinPost($thread, $post))
        {
            return $this->noPermission();
        }

        if (!$this->isPost())
        {
            return $this->view(
                'Sky\PinnedPosts:PinConfirm',
                'sky_pinnedposts_pin_confirm',
                [
                    'thread' => $thread,
                    'post' => $post,
                ]
            );
        }

        $this->assertPostOnly();
        $pinLabel = $this->filter('pin_label', 'str');
        $result = $repo->pinPost($thread, $post, \XF::visitor(), $pinLabel);

        if ($result === false)
        {
            return $this->error(\XF::phrase('sky_pinnedposts.limit_reached'));
        }

        if ($result === null)
        {
            return $this->error(\XF::phrase('sky_pinnedposts.already_pinned'));
        }

        return $this->redirect($this->getPostUrl($thread, $post->post_id));
    }

    public function actionUnpinPost(ParameterBag $params)
    {
        $thread = $this->assertViewableThread($params->thread_id);
        $repo = $this->getPinnedPostRepo();

        $postId = $this->filter('post_id', 'uint');
        $post = $this->em()->find('XF:Post', $postId);

        if (!$post || $post->thread_id != $thread->thread_id)
        {
            return $this->notFound();
        }

        if (!$repo->canUserUnpinPost($thread, $post))
        {
            return $this->noPermission();
        }

        if (!$this->isPost())
        {
            return $this->view(
                'Sky\PinnedPosts:UnpinConfirm',
                'sky_pinnedposts_unpin_confirm',
                [
                    'thread' => $thread,
                    'post' => $post,
                ]
            );
        }

        $this->assertPostOnly();
        $repo->unpinPost($post->post_id, $thread->thread_id);

        return $this->redirect($this->getPostUrl($thread, $post->post_id));
    }

    public function actionPinMove(ParameterBag $params)
    {
        $thread = $this->assertViewableThread($params->thread_id);
        $repo = $this->getPinnedPostRepo();

        if (!$repo->canUserManagePinsInThread($thread))
        {
            return $this->noPermission();
        }

        $this->assertPostOnly();

        $pinnedPostId = $this->filter('pinned_post_id', 'uint');
        $direction = $this->filter('direction', 'str');

        if (!in_array($direction, ['up', 'down'], true))
        {
            return $this->notFound();
        }

        if (!$repo->movePinnedPost($pinnedPostId, $direction, $thread->thread_id))
        {
            return $this->notFound();
        }

        return $this->redirect($this->buildLink('threads', $thread));
    }

    protected function getPostUrl(\XF\Entity\Thread $thread, int $postId): string
    {
        return $this->buildLink('threads', $thread, ['post_id' => $postId]) . '#post-' . $postId;
    }

    /** @return \Sky\PinnedPosts\Repository\PinnedPostRepo */
    protected function getPinnedPostRepo()
    {
        return $this->repository('Sky\PinnedPosts:PinnedPostRepo');
    }
}
