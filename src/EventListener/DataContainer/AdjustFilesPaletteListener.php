<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoAutoDeleteFiles\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Symfony\Component\Filesystem\Path;

#[AsCallback('tl_files', 'config.onload')]
class AdjustFilesPaletteListener
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function __invoke(DataContainer $dc): void
    {
        if ($dc->id && is_dir(Path::join($this->projectDir, $dc->id))) {
            $GLOBALS['TL_DCA']['tl_files']['palettes']['default'] .= ';autoDeleteFilesTime,autoDeleteFilesCount';
        }
    }
}
