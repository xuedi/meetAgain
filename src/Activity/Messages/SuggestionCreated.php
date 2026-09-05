<?php declare(strict_types=1);

namespace App\Activity\Messages;

use App\Activity\MessageAbstract;

class SuggestionCreated extends MessageAbstract
{
    public const string TYPE = 'core.suggestion_created';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function validate(): MessageAbstract
    {
        $this->ensureHasKey('description');

        return $this;
    }

    protected function renderText(): string
    {
        return $this->translator->trans('profile_social.activity_suggestion_created', [
            '%target%' => $this->meta['description'],
        ]);
    }

    protected function renderHtml(): string
    {
        return $this->translator->trans('profile_social.activity_suggestion_created', [
            '%target%' => '<strong>' . $this->escapeHtml($this->meta['description']) . '</strong>',
        ]);
    }
}
