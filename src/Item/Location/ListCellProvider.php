<?php declare(strict_types=1);

namespace App\Item\Location;

use App\Enum\ItemViewType;
use App\Item\ListCellProviderInterface;
use App\Repository\LocationRepository;
use Override;
use Twig\Environment;

final readonly class ListCellProvider implements ListCellProviderInterface
{
    public const string ITEM_TYPE = 'location';

    public function __construct(
        private LocationRepository $repo,
        private Environment $twig,
    ) {}

    #[Override]
    public function getPluginKey(): string
    {
        return '';
    }

    #[Override]
    public function getKey(): string
    {
        return self::ITEM_TYPE;
    }

    #[Override]
    public function renderListCell(int $itemId, ?ItemViewType $mode = null): ?string
    {
        $location = $this->repo->find($itemId);
        if ($location === null) {
            return null;
        }

        return $this->twig->render('item/location/cell.html.twig', [
            'location' => $location,
            'viewMode' => $mode?->value,
        ]);
    }
}
