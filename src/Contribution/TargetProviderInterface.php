<?php declare(strict_types=1);

namespace App\Contribution;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Registers one kind of row as something a member may help correct. The registry keys providers by
 * getType(); core never learns what a type means.
 */
#[AutoconfigureTag]
interface TargetProviderInterface
{
    /** Registry key for this kind of row, and the `{type}` segment in the hub's routes. */
    public function getType(): string;

    /** Plugin that owns this section, or the empty string for core. */
    public function getPluginKey(): string;

    /** Translation key for the section label. */
    public function getLabelKey(): string;

    public function getIcon(): string;

    /**
     * What this member may currently help with, before the scope chain narrows it. An entry is
     * identified by whatever the provider addresses a row with: an entity id, or a key of its own
     * where the section's rows are not table rows.
     *
     * @return list<Entry>
     */
    public function listForMember(User $user): array;

    /**
     * The per-row rule the hub's forms and every inbound link share. Implementations answer the
     * union of every route that can reach the row, never one route's rule alone.
     */
    public function mayTouch(User $user, int|string $id): bool;
}
