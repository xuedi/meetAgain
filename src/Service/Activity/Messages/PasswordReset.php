<?php declare(strict_types=1);

namespace App\Service\Activity\Messages;

use App\Service\Activity\MessageAbstract;
use App\Entity\ActivityType;

class PasswordReset extends MessageAbstract
{
    public function getType(): ActivityType
    {
        return ActivityType::PasswordReset;
    }

    public function validate(): bool
    {
        return true;
    }

    protected function renderText(): string
    {
        return 'Retested password';
    }

    protected function renderHtml(): string
    {
        return 'Retested password';
    }
}
