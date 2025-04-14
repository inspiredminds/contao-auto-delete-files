<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoAutoDeleteFiles;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoAutoDeleteFilesBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
