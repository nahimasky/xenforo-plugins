<?php

namespace Sky\BadgeStudio\XF\Finder;

use XF\Mvc\Entity\Manager;
use XF\Mvc\Entity\Structure;

/**
 * Eager-loads the single Badge Studio assignment whenever XenForo fetches users
 * for a public list or card, avoiding a lazy relation query for every avatar.
 */
class User extends XFCP_User
{
    public function __construct(Manager $em, Structure $structure)
    {
        parent::__construct($em, $structure);

        $this->with('BadgeStudioUserBadge.Badge');
    }
}
