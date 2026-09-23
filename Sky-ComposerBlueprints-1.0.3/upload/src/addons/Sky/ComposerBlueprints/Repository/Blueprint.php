<?php

namespace Sky\ComposerBlueprints\Repository;

use Sky\ComposerBlueprints\Service\BlueprintScopeMatcher;
use XF\Mvc\Entity\Repository;

class Blueprint extends Repository
{
    public function findActiveBlueprints()
    {
        return $this->finder('Sky\ComposerBlueprints:Blueprint')
            ->where('active', true)
            ->order('display_order')
            ->order('title');
    }

    public function getAvailableBlueprintsForContext(int $nodeId, string $context): array
    {
        $available = [];

        foreach ($this->findActiveBlueprints()->fetch() as $blueprint)
        {
            if (BlueprintScopeMatcher::matches($blueprint->contexts, $blueprint->node_ids, $context, $nodeId))
            {
                $available[] = $blueprint;
            }
        }

        return $available;
    }
}
