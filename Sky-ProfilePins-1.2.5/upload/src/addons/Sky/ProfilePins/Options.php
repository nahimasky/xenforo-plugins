<?php

namespace Sky\ProfilePins;

/**
 * Accessor for XenForo ACP options. Mutable configuration is defined in
 * _data/options.xml and is read from the XF options repository at runtime.
 */
class Options
{
    public static function enabled(): bool
    {
        return (bool)\XF::options()->skyProfilePinsEnabled;
    }

    public static function maximumPinsPerWall(): int
    {
        return min(10, max(1, (int)\XF::options()->skyProfilePinsMaximumPerWall));
    }
}
