<?php declare(strict_types=1);

namespace App\Entity;

use Symfony\Contracts\Translation\TranslatorInterface;

enum UserActivity: int
{
    case ChangedUsername = 0;
    case Login = 1;

    // TODO: should be separate translator not here in enum
    public static function getChoices(TranslatorInterface $translator): array
    {
        return [
            $translator->trans('ChangedUsername') => self::ChangedUsername,
            $translator->trans('Login') => self::Login,
            // TODO: send message, did rsvp, wrote comment
        ];
    }
}
