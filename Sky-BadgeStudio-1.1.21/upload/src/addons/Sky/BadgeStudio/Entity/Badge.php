<?php

namespace Sky\BadgeStudio\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use Sky\BadgeStudio\IconLibrary;

/**
 * COLUMNS
 * @property int $badge_id
 * @property string $title
 * @property string $icon
 * @property string $custom_fa_icon
 * @property string $color
 * @property int $created_date
 */
class Badge extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_bs_badge';
        $structure->shortName = 'Sky\BadgeStudio:Badge';
        $structure->primaryKey = 'badge_id';
        $structure->columns = [
            'badge_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'title' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'icon' => ['type' => self::STR, 'maxLength' => 30, 'default' => 'star'],
            'custom_fa_icon' => ['type' => self::STR, 'maxLength' => 60, 'default' => ''],
            'color' => [
                'type' => self::STR,
                'maxLength' => 7,
                'default' => '#C81E4D',
                'match' => '/^#[0-9a-fA-F]{6}$/',
            ],
            'created_date' => ['type' => self::UINT, 'default' => \XF::$time],
        ];

        return $structure;
    }

    public function getIconSvg(): string
    {
        $icons = IconLibrary::getIcons();
        return $icons[$this->icon]['svg'] ?? '';
    }

    /**
     * Returns only a canonical Font Awesome 5 family and icon pair.
     * Dynamic <xf:fa> values are treated as untrusted even when stored.
     */
    public static function normalizeCustomFaIcon(string $icon): string
    {
        $icon = preg_replace('/\s+/', ' ', trim($icon));

        if (preg_match('/^fa-[a-z0-9-]+$/', $icon))
        {
            return 'fas ' . $icon;
        }

        return preg_match('/^(?:fas|far|fab) fa-[a-z0-9-]+$/', $icon) ? $icon : '';
    }

    public function getCustomFaIcon(): string
    {
        return self::normalizeCustomFaIcon((string)$this->custom_fa_icon);
    }

    protected function _preDelete()
    {
        $this->db()->delete('xf_bs_user_badge', 'badge_id = ?', $this->badge_id);
    }

    public function getIconLetter(): string
    {
        $title = trim((string)$this->title);
        if ($title === '')
        {
            return '?';
        }

        return mb_strtoupper(mb_substr($title, 0, 1));
    }
}
