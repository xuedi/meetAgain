<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum CardState: string
{
    case New = 'new';
    case Learning = 'learning';
    case Review = 'review';
    case Relearning = 'relearning';
}
