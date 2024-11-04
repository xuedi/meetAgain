<?php declare(strict_types=1);

namespace App\Entity;

enum ConfigType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case String = 'string';
}
