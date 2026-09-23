<?php

namespace Sky\ComposerBlueprints\Service;

use Sky\ComposerBlueprints\Entity\Blueprint;

class BlueprintAvailability
{
    public function getAvailableBlueprints(string $context, int $nodeId, int $threadId = 0): array
    {
        if (!in_array($context, Blueprint::VALID_CONTEXTS, true))
        {
            return [];
        }

        $visitor = \XF::visitor();
        if (!$visitor->hasPermission('skyComposerBlueprints', 'useComposerBlueprints'))
        {
            return [];
        }

        $forum = \XF::finder('XF:Forum')
            ->whereId($nodeId)
            ->fetchOne();
        if (!$forum || !$forum->canView())
        {
            return [];
        }

        if ($context === Blueprint::CONTEXT_NEW_THREAD)
        {
            if (!$forum->canCreateThread())
            {
                return [];
            }
        }
        else
        {
            $thread = \XF::finder('XF:Thread')
                ->whereId($threadId)
                ->fetchOne();
            $error = null;
            if (!$thread || $thread->node_id !== $forum->node_id || !$thread->canView() || !$thread->canReply($error))
            {
                return [];
            }
        }

        return \XF::repository('Sky\ComposerBlueprints:Blueprint')
            ->getAvailableBlueprintsForContext($forum->node_id, $context);
    }
}
