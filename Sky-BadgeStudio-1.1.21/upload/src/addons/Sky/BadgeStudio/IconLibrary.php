<?php

namespace Sky\BadgeStudio;

/**
 * Простой набор иконок для бейджей. Каждая иконка — самостоятельная SVG-разметка
 * (viewBox 0 0 24 24), не зависящая от сторонних библиотек.
 */
class IconLibrary
{
    public static function getIcons(): array
    {
        return [
            'star' => [
                'label' => 'Звезда',
                'svg' => '<polygon points="12,2 14.9,8.6 22,9.3 16.7,14.1 18.2,21.2 12,17.5 5.8,21.2 7.3,14.1 2,9.3 9.1,8.6" />',
            ],
            'crown' => [
                'label' => 'Корона',
                'svg' => '<path d="M3 8 L7 12 L12 4 L17 12 L21 8 L19 18 L5 18 Z" />',
            ],
            'shield' => [
                'label' => 'Щит',
                'svg' => '<path d="M12 2 L20 5 V11 C20 16 16.5 20 12 22 C7.5 20 4 16 4 11 V5 Z" />',
            ],
            'bolt' => [
                'label' => 'Молния',
                'svg' => '<polygon points="13,2 4,14 11,14 9,22 20,9 13,9" />',
            ],
            'flame' => [
                'label' => 'Пламя',
                'svg' => '<path d="M12 2 C8 7 6 10 6 14 A6 6 0 0 0 18 14 C18 11 16 9 15 7 C15 10 13 11 12 9 C11 6 12 4 12 2 Z" />',
            ],
            'gem' => [
                'label' => 'Камень',
                'svg' => '<polygon points="6,3 18,3 22,9 12,22 2,9" />',
            ],
            'heart' => [
                'label' => 'Сердце',
                'svg' => '<path d="M12 21 C7 16.5 3 13 3 8.5 A4.5 4.5 0 0 1 12 6.5 A4.5 4.5 0 0 1 21 8.5 C21 13 17 16.5 12 21 Z" />',
            ],
            'check' => [
                'label' => 'Галочка',
                'svg' => '<polyline points="4,13 9,18 20,6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />',
            ],
            'music' => [
                'label' => 'Музыка',
                'svg' => '<path d="M9 18 A3 3 0 1 1 9 12 A3 3 0 0 1 9 18 Z M9 15 V4 L20 2 V13" fill="none" stroke="currentColor" stroke-width="1.6" />',
            ],
            'award' => [
                'label' => 'Награда',
                'svg' => '<circle cx="12" cy="8" r="6" /><polyline points="8,13 6,22 12,18 18,22 16,13" fill="none" stroke="currentColor" stroke-width="1.6" />',
            ],
            'target' => [
                'label' => 'Цель',
                'svg' => '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="12" r="5" fill="none" stroke="currentColor" stroke-width="1.6" /><circle cx="12" cy="12" r="1.5" />',
            ],
            'letter' => [
                'label' => 'Буква (по названию бейджа)',
                'svg' => '', // спецкейс — обрабатывается в шаблоне отдельно, выводит первую букву title
            ],
        ];
    }

    public static function getIconKeys(): array
    {
        return array_keys(static::getIcons());
    }
}
