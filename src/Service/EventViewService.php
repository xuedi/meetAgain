<?php declare(strict_types=1);

namespace App\Service;

use App\Repository\EventRepository;

class EventViewService
{
    public function __construct(private EventRepository $repo)
    {
    }

    public function getList(): array
    {
        $structuredList = [];

        $events = $this->repo->findBy([], ['start' => 'ASC']);
        foreach ($events as $event) {
            $key = $event->getStart()->format('Y-m');

            if (!isset($structuredList[$key])) {
                $structuredList[$key] = [
                    'year' =>  $event->getStart()->format('Y'),
                    'month' =>  $event->getStart()->format('F'),
                    'events' => [],
                ];
            }
            $structuredList[$key]['events'][] = $event;
        }

        return $structuredList;
    }
}
