<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Event;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contributes an extra entry to the venue dropdown of the admin event form. Union chain: the form
 * renders every available provider's entry, and picking one hands the event to that provider along
 * with whatever terms the operator set in the entry's overlay.
 */
#[AutoconfigureTag]
interface LocationChoiceProviderInterface
{
    public function getPluginKey(): string;

    public function getValue(): string;

    public function getLabelKey(): string;

    public function isAvailableFor(?Event $event): bool;

    public function isActiveFor(Event $event): bool;

    /** @param array<string, mixed> $terms the overlay's answers, empty when the entry has no overlay */
    public function choose(Event $event, array $terms): void;

    public function release(Event $event): void;
}
