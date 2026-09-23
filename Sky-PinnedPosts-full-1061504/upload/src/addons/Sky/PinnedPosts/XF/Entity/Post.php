<?php

/**
 * Sky/PinnedPosts
 *
 * Class extension: XF\Entity\Post
 */

namespace Sky\PinnedPosts\XF\Entity;

class Post extends XFCP_Post
{
    /**
     * A hidden or moderated post must no longer remain pinned. This is deliberate:
     * restoring its visibility later does not silently recreate a pin.
     */
    protected function _postSave()
    {
        parent::_postSave();

        if (
            $this->isUpdate()
            && $this->isChanged('message_state')
            && $this->message_state != 'visible'
        )
        {
            \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->deletePinsForPost($this->post_id);
        }
    }

    /**
     * Ensures no orphan row remains when XenForo permanently deletes a post.
     */
    protected function _postDelete()
    {
        parent::_postDelete();

        \XF::repository('Sky\PinnedPosts:PinnedPostRepo')->deletePinsForPost($this->post_id);
    }
}
