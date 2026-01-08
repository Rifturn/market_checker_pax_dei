<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SpellExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('spell_icon_url', [$this, 'formatSpellIconUrl']),
        ];
    }

    public function formatSpellIconUrl(?string $iconPath): string
    {
        if (!$iconPath) {
            return '';
        }

        // Remplacer {height} par 128
        $iconPath = preg_replace('/\{height\}/', '128', $iconPath);

        // Ajouter le préfixe
        return 'https://gtcdn.info/paxdei' . $iconPath;
    }
}
