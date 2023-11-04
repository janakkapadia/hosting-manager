<?php

namespace JanakKapadia\HostingManager\Enums\ServerAvatar;

enum CreateSiteEnum
{
    public static function methods(): array
    {
        return [
            'custom',
            'one_click',
            'git'
        ];
    }

    public static function frameworks($method): array
    {
        return match ($method) {
            'custom' => ['custom'],
            'git' => ['bitbucket', 'github', 'gitlab'],
            'one_click' => ['wordpress', 'mautic', 'moodle', 'joomla', 'prestashop']
        };
    }

}
