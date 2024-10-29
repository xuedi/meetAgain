<?php

namespace App\Entity;

enum ConfigType: string
{
    case Boolean = 'boolean';
    case Integer = 'integer';
    case String = 'string';
}
