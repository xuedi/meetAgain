<?php declare(strict_types=1);

namespace App\Contribution;

use App\Entity\User;
use App\Item\Tag\TypeRegistry;
use Override;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TagSection implements TargetProviderInterface
{
    public const string TYPE = 'tag';

    public function __construct(
        private TypeRegistry $types,
        private Security $security,
        private TranslatorInterface $translator,
    ) {}

    #[Override]
    public function getType(): string
    {
        return self::TYPE;
    }

    #[Override]
    public function getPluginKey(): string
    {
        return '';
    }

    #[Override]
    public function getLabelKey(): string
    {
        return 'contribution.section_tags';
    }

    #[Override]
    public function getIcon(): string
    {
        return 'fa-tags';
    }

    #[Override]
    public function listForMember(User $user): array
    {
        if ($this->security->isGranted('ROLE_STEWARD')) {
            return [];
        }

        $entries = [];
        foreach ($this->types->all() as $type) {
            $entries[] = new Entry($type->getTypeKey(), $this->translator->trans($type->getLabelKey()));
        }

        return $entries;
    }

    #[Override]
    public function mayTouch(User $user, int|string $id): bool
    {
        return !$this->security->isGranted('ROLE_STEWARD') && $this->types->has((string) $id);
    }
}
