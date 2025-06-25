<?php declare(strict_types=1);

namespace App\Service\Activity\Messages;

use App\Service\Activity\MessageAbstract;
use App\Entity\ActivityType;

class RsvpNo extends MessageAbstract
{
    public function getType(): ActivityType
    {
        return ActivityType::RsvpNo;
    }

    public function validate(): void
    {
        $this->ensureHasKey('event_id');
        $this->ensureIsNumeric('event_id');
    }

    protected function renderText(): string
    {
        $eventId = $this->meta['event_id'];
        $msgTemplate = 'Is skipping event: %s';
        return sprintf(
            $msgTemplate,
            $this->eventNames[$eventId],
        );
    }

    protected function renderHtml(): string
    {
        $eventId = $this->meta['event_id'];
        $msgTemplate = 'Is skipping event: %s'; // TODO: link
        return sprintf(
            $msgTemplate,
            $this->eventNames[$eventId],
        );
    }
}
