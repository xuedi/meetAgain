<?php declare(strict_types=1);

namespace Plugin\Glossary\Enum;

enum AnswerMode: string
{
    case Flip = 'flip';
    case Choice = 'choice';
    case Typing = 'typing';

    public function label(): string
    {
        return match ($this) {
            self::Flip => 'glossary_trainer.answer_mode_flip',
            self::Choice => 'glossary_trainer.answer_mode_choice',
            self::Typing => 'glossary_trainer.answer_mode_typing',
        };
    }
}
