<?php declare(strict_types=1);

namespace App\DataHotfix\Hotfixes;

use App\DataHotfix\DataHotfixInterface;
use App\Item\Tag\AssignmentClosure;
use Doctrine\ORM\EntityManagerInterface;
use Override;

readonly class RestoreItemTagClosure implements DataHotfixInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private AssignmentClosure $closure,
    ) {}

    #[Override]
    public function getIdentifier(): string
    {
        return '2026_09_12_item_tag_closure';
    }

    #[Override]
    public function execute(): void
    {
        foreach ($this->em->getConnection()->fetchFirstColumn('SELECT DISTINCT item_type FROM item_tag') as $itemType) {
            $this->closure->restore((string) $itemType);
        }
    }
}
