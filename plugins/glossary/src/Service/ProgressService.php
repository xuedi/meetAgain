<?php declare(strict_types=1);

namespace Plugin\Glossary\Service;

use App\Item\Tag\TagService;
use App\Repository\ItemTagAssignmentRepository;
use DateInterval;
use DateTimeImmutable;
use Plugin\Glossary\Entity\Glossary;
use Plugin\Glossary\Entity\TrainerCard;
use Plugin\Glossary\Enum\CardState;
use Plugin\Glossary\Enum\Scope;
use Plugin\Glossary\Item\GlossaryTaggableTypeProvider;
use Plugin\Glossary\Repository\TrainerCardRepository;
use Plugin\Glossary\Repository\TrainerDayRepository;

readonly class ProgressService
{
    private const int UPCOMING_DAYS = 7;
    private const int ACTIVITY_DAYS = 14;
    private const int HARDEST_LIMIT = 10;
    private const int LEARNED_INTERVAL_DAYS = 21;
    private const int STREAK_WINDOW_DAYS = 400;
    private const int BAND_MIN_LEARNERS = 3;
    private const int LEADERBOARD_SIZE = 10;

    public function __construct(
        private TrainerService $trainer,
        private TrainerCardRepository $cardRepo,
        private TrainerDayRepository $dayRepo,
        private TagService $tagService,
        private ItemTagAssignmentRepository $assignmentRepo,
    ) {}

    /** @return array{streak: int, due: int, started: int, learned: int, total: int} */
    public function summary(int $userId, DateTimeImmutable $now): array
    {
        $visible = $this->trainer->visibleIds();

        return $this->summaryOf($userId, $visible, $this->entryStates($this->cards($userId, $visible)), $now);
    }

    /**
     * @return array{
     *     summary: array{streak: int, due: int, started: int, learned: int, total: int},
     *     answers: int,
     *     correct: int,
     *     starred: int,
     *     upcoming: array<string, int>,
     *     activity: list<array{day: DateTimeImmutable, reviewed: int, correct: int}>,
     *     tags: list<array{label: string, total: int, started: int, learned: int}>,
     *     hardest: list<array{entry: Glossary, seen: int, missed: int}>,
     *     suspended: list<Glossary>
     * }
     */
    public function details(int $userId, ?string $locale, DateTimeImmutable $now): array
    {
        $visible = $this->trainer->visibleIds();
        $cards = $this->cards($userId, $visible);
        $states = $this->entryStates($cards);
        $totals = $this->answerTotals($cards);

        return [
            'summary' => $this->summaryOf($userId, $visible, $states, $now),
            'answers' => $totals['seen'],
            'correct' => $totals['correct'],
            'starred' => count(array_intersect($visible, $this->cardRepo->markedGlossaryIds($userId))),
            'upcoming' => $this->upcoming($cards, $now),
            'activity' => $this->activity($userId, $now),
            'tags' => $this->tagProgress($visible, $states, $locale),
            'hardest' => $this->hardest($cards),
            'suspended' => $this->trainer->entries(array_values(array_intersect($visible, $this->cardRepo->suspendedGlossaryIds($userId)))),
        ];
    }

    /** @return array{cards: list<TrainerCard>, seen: int, wrong: int, marked: bool, suspended: bool} */
    public function entryStats(int $userId, int $glossaryId): array
    {
        $cards = $this->cardRepo->findForEntry($userId, $glossaryId);
        $totals = $this->answerTotals($cards);

        return [
            'cards' => array_values(array_filter($cards, static fn(TrainerCard $card): bool => $card->getState() !== CardState::New)),
            'seen' => $totals['seen'],
            'wrong' => $totals['seen'] - $totals['correct'],
            'marked' => array_any($cards, static fn(TrainerCard $card): bool => $card->isMarked()),
            'suspended' => array_any($cards, static fn(TrainerCard $card): bool => $card->isSuspended()),
        ];
    }

    public function difficultyBand(int $glossaryId): ?string
    {
        ['learners' => $learners, 'lapsed' => $lapsed] = $this->cardRepo->lapseCounts($glossaryId);
        if ($learners < self::BAND_MIN_LEARNERS) {
            return null;
        }

        $share = $lapsed / $learners;

        return match (true) {
            $share >= 0.4 => 'glossary_trainer.band_hard',
            $share >= 0.15 => 'glossary_trainer.band_tricky',
            default => 'glossary_trainer.band_easy',
        };
    }

    public function streak(int $userId, DateTimeImmutable $now): int
    {
        $today = $now->setTime(0, 0);
        $active = array_flip($this->dayRepo->activeDays($userId, $today->sub(new DateInterval(sprintf('P%dD', self::STREAK_WINDOW_DAYS)))));
        $oneDay = new DateInterval('P1D');

        $cursor = isset($active[$today->format('Y-m-d')]) ? $today : $today->sub($oneDay);
        $streak = 0;
        while (isset($active[$cursor->format('Y-m-d')])) {
            ++$streak;
            $cursor = $cursor->sub($oneDay);
        }

        return $streak;
    }

    /** @return array<int, int> user id => answers given on the visible entries; empty unless the config enables it */
    public function leaderboard(): array
    {
        if (!$this->trainer->config()->isLeaderboardEnabled()) {
            return [];
        }

        return $this->cardRepo->answerCountsByUser($this->trainer->visibleIds(), self::LEADERBOARD_SIZE);
    }

    /**
     * @param list<int> $visible
     * @param array<int, bool> $states
     * @return array{streak: int, due: int, started: int, learned: int, total: int}
     */
    private function summaryOf(int $userId, array $visible, array $states, DateTimeImmutable $now): array
    {
        return [
            'streak' => $this->streak($userId, $now),
            'due' => count($this->trainer->scopeIds(Scope::Due, [], $userId, $now)),
            'started' => count($states),
            'learned' => count(array_filter($states)),
            'total' => count($visible),
        ];
    }

    /**
     * @param list<TrainerCard> $cards
     * @return array{seen: int, correct: int}
     */
    private function answerTotals(array $cards): array
    {
        return [
            'seen' => (int) array_sum(array_map(static fn(TrainerCard $card): int => $card->getTimesSeen(), $cards)),
            'correct' => (int) array_sum(array_map(static fn(TrainerCard $card): int => $card->getTimesCorrect(), $cards)),
        ];
    }

    /**
     * @param list<int> $visible
     * @return list<TrainerCard>
     */
    private function cards(int $userId, array $visible): array
    {
        $wanted = array_flip($visible);

        return array_values(array_filter(
            $this->cardRepo->findBy(['userId' => $userId]),
            static fn(TrainerCard $card): bool => isset($wanted[(int) $card->getGlossary()->getId()]),
        ));
    }

    /**
     * @param list<TrainerCard> $cards
     * @return array<int, bool> glossary id => learned, for every entry answered at least once
     */
    private function entryStates(array $cards): array
    {
        $states = [];
        foreach ($cards as $card) {
            if ($card->getTimesSeen() === 0) {
                continue;
            }

            $id = (int) $card->getGlossary()->getId();
            $mature = $card->getState() === CardState::Review && $card->getIntervalDays() >= self::LEARNED_INTERVAL_DAYS;
            $states[$id] = ($states[$id] ?? true) && $mature;
        }

        return $states;
    }

    /**
     * @param list<TrainerCard> $cards
     * @return array<string, int> Y-m-d => entries due that day, today first; overdue entries count as today
     */
    private function upcoming(array $cards, DateTimeImmutable $now): array
    {
        $today = $now->setTime(0, 0);
        $days = [];
        for ($offset = 0; $offset < self::UPCOMING_DAYS; ++$offset) {
            $days[$today->add(new DateInterval(sprintf('P%dD', $offset)))->format('Y-m-d')] = [];
        }

        foreach ($cards as $card) {
            $dueAt = $card->getDueAt();
            if ($dueAt === null || $card->isSuspended() || $card->getState() === CardState::New) {
                continue;
            }

            $key = ($dueAt < $today ? $today : $dueAt)->format('Y-m-d');
            if (isset($days[$key])) {
                $days[$key][(int) $card->getGlossary()->getId()] = true;
            }
        }

        return array_map(count(...), $days);
    }

    /** @return list<array{day: DateTimeImmutable, reviewed: int, correct: int}> oldest first */
    private function activity(int $userId, DateTimeImmutable $now): array
    {
        $logged = [];
        foreach ($this->dayRepo->findBy(['userId' => $userId], ['day' => 'DESC'], self::ACTIVITY_DAYS) as $day) {
            $logged[$day->getDay()->format('Y-m-d')] = $day;
        }

        $today = $now->setTime(0, 0);
        $activity = [];
        for ($offset = self::ACTIVITY_DAYS - 1; $offset >= 0; --$offset) {
            $date = $today->sub(new DateInterval(sprintf('P%dD', $offset)));
            $day = $logged[$date->format('Y-m-d')] ?? null;
            $activity[] = ['day' => $date, 'reviewed' => $day?->getReviewed() ?? 0, 'correct' => $day?->getCorrect() ?? 0];
        }

        return $activity;
    }

    /**
     * @param list<int> $visible
     * @param array<int, bool> $states
     * @return list<array{label: string, total: int, started: int, learned: int}>
     */
    private function tagProgress(array $visible, array $states, ?string $locale): array
    {
        $counts = [];
        foreach ($this->assignmentRepo->tagIdsForItems(GlossaryTaggableTypeProvider::ITEM_TYPE, $visible) as $glossaryId => $tagIds) {
            foreach ($tagIds as $tagId) {
                $counts[$tagId] ??= ['total' => 0, 'started' => 0, 'learned' => 0];
                ++$counts[$tagId]['total'];
                if (isset($states[$glossaryId])) {
                    ++$counts[$tagId]['started'];
                    $counts[$tagId]['learned'] += $states[$glossaryId] ? 1 : 0;
                }
            }
        }

        $progress = [];
        foreach ($this->tagService->getChoices(GlossaryTaggableTypeProvider::ITEM_TYPE, $locale) as $tagId => $label) {
            if (!isset($counts[$tagId])) {
                continue;
            }

            $progress[] = ['label' => trim($label), ...$counts[$tagId]];
        }

        return $progress;
    }

    /**
     * @param list<TrainerCard> $cards
     * @return list<array{entry: Glossary, seen: int, missed: int}> most missed first
     */
    private function hardest(array $cards): array
    {
        $tally = [];
        foreach ($cards as $card) {
            $id = (int) $card->getGlossary()->getId();
            $tally[$id] ??= ['seen' => 0, 'missed' => 0];
            $tally[$id]['seen'] += $card->getTimesSeen();
            $tally[$id]['missed'] += $card->getTimesSeen() - $card->getTimesCorrect();
        }

        $tally = array_filter($tally, static fn(array $row): bool => $row['missed'] > 0);
        uasort($tally, static fn(array $a, array $b): int => [$b['missed'], $a['seen']] <=> [$a['missed'], $b['seen']]);

        $hardest = [];
        foreach ($this->trainer->entries(array_slice(array_keys($tally), 0, self::HARDEST_LIMIT)) as $entry) {
            $hardest[] = ['entry' => $entry, ...$tally[(int) $entry->getId()]];
        }

        return $hardest;
    }
}
