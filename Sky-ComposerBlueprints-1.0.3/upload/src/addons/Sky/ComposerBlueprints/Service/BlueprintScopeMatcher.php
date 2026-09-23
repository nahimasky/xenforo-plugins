<?php

namespace Sky\ComposerBlueprints\Service;

class BlueprintScopeMatcher
{
    public static function matches(array $contexts, array $nodeIds, string $context, int $nodeId): bool
    {
        if (!in_array($context, $contexts, true))
        {
            return false;
        }

        return !$nodeIds || in_array($nodeId, $nodeIds, true);
    }
}
