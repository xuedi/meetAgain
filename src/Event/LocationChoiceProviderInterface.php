<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Event;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contributes an extra entry to the venue dropdown of the admin event form. Union chain: the form
 * renders every available provider's entry, and picking one hands the event to that provider.
 */
#[AutoconfigureTag]
interface LocationChoiceProviderInterface
{
    public function getPluginKey(): string;

    public function getValue(): string;

    public function getLabelKey(): string;

    public function isAvailableFor(?Event $event): bool;

    public function isActiveFor(Event $event): bool;

    public function choose(Event $event): void;

    public function release(Event $event): void;
}
