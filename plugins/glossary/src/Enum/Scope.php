<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum Scope: string
{
    case Selection = 'selection';
    case Due = 'due';
    case Starred = 'starred';
    case Worst = 'worst';
    case Unseen = 'unseen';

    public function label(): string
    {
        return match ($this) {
            self::Selection => 'glossary_trainer.scope_selection',
            self::Due => 'glossary_trainer.scope_due',
            self::Starred => 'glossary_trainer.scope_starred',
            self::Worst => 'glossary_trainer.scope_worst',
            self::Unseen => 'glossary_trainer.scope_unseen',
        };
    }
}
