<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum Direction: string
{
    case TermToDefinition = 'term_to_definition';
    case DefinitionToTerm = 'definition_to_term';
    case SecondaryToTerm = 'secondary_to_term';

    public function label(): string
    {
        return match ($this) {
            self::TermToDefinition => 'glossary_trainer.direction_term_to_definition',
            self::DefinitionToTerm => 'glossary_trainer.direction_definition_to_term',
            self::SecondaryToTerm => 'glossary_trainer.direction_secondary_to_term',
        };
    }

    public function answersWithTerm(): bool
    {
        return $this !== self::TermToDefinition;
    }
}
