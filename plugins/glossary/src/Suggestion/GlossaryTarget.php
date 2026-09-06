<?php declare(strict_types=1);

namespace Plugin\Glossary\Suggestion;

use App\Entity\User;
use App\Suggestion\SuggestionException;
use App\Suggestion\SuggestionTargetProviderInterface;
use Override;
use Plugin\Glossary\Entity\Glossary;
use Plugin\Glossary\Form\GlossaryType;
use Plugin\Glossary\Item\GlossaryTaggableTypeProvider;
use Plugin\Glossary\Service\ConfigService;
use Plugin\Glossary\Service\GlossaryService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GlossaryTarget implements SuggestionTargetProviderInterface
{
    public const string TARGET_TYPE = GlossaryTaggableTypeProvider::ITEM_TYPE;

    public function __construct(
        private GlossaryService $service,
        private ConfigService $configService,
        private Security $security,
        private TranslatorInterface $translator,
    ) {}

    #[Override]
    public function getPluginKey(): string
    {
        return 'glossary';
    }

    #[Override]
    public function getTargetType(): string
    {
        return self::TARGET_TYPE;
    }

    #[Override]
    public function getLabelKey(): string
    {
        return 'glossary.entry_label';
    }

    #[Override]
    public function getFormType(): string
    {
        return GlossaryType::class;
    }

    #[Override]
    public function newDraft(): object
    {
        return new Glossary();
    }

    #[Override]
    public function fromPayload(array $payload): object
    {
        $draft = new Glossary();
        $draft->setPhrase($this->text($payload, 'phrase'));
        $draft->setExplanation($this->text($payload, 'explanation'));
        $draft->setPinyin($this->optionalText($payload, 'pinyin'));

        return $draft;
    }

    #[Override]
    public function toPayload(object $draft): array
    {
        $entry = $this->entry($draft);

        return [
            'phrase' => (string) $entry->getPhrase(),
            'pinyin' => $entry->getPinyin(),
            'explanation' => (string) $entry->getExplanation(),
        ];
    }

    #[Override]
    public function describe(array $payload): string
    {
        return $this->translator->trans('glossary.suggestion_description', [
            '%phrase%' => $this->text($payload, 'phrase'),
        ]);
    }

    #[Override]
    public function summaryRows(array $payload): array
    {
        $config = $this->configService->getConfig();

        $rows = [[
            'label' => $config->getPrimaryLabel() ?? $this->translator->trans('glossary.label_phrase'),
            'value' => $this->text($payload, 'phrase'),
        ]];

        if ($config->isSecondaryEnabled()) {
            $rows[] = [
                'label' => $config->getSecondaryLabel() ?? $this->translator->trans('glossary.label_pinyin'),
                'value' => $this->text($payload, 'pinyin'),
            ];
        }

        $rows[] = [
            'label' => $config->getDefinitionLabel() ?? $this->translator->trans('glossary.label_explanation'),
            'value' => $this->text($payload, 'explanation'),
        ];

        return $rows;
    }

    #[Override]
    public function canPropose(User $user): bool
    {
        return $this->security->isGranted('ROLE_USER');
    }

    #[Override]
    public function canReview(User $user): bool
    {
        return $this->security->isGranted('ROLE_ORGANIZER');
    }

    #[Override]
    public function validate(object $draft): ?string
    {
        $entry = $this->entry($draft);
        $phrase = trim((string) $entry->getPhrase());
        if ($phrase === '' || trim((string) $entry->getExplanation()) === '') {
            return $this->translator->trans('glossary.validator_incomplete');
        }

        return $this->isDuplicate($phrase) ? $this->translator->trans('glossary.validator_duplicate') : null;
    }

    #[Override]
    public function create(object $draft, User $proposer): int
    {
        $entry = $this->entry($draft);
        $this->service->create($entry, (int) $proposer->getId());

        return (int) $entry->getId();
    }

    private function entry(object $draft): Glossary
    {
        if (!$draft instanceof Glossary) {
            throw new SuggestionException(sprintf('Expected a Glossary draft, got %s', $draft::class));
        }

        return $draft;
    }

    private function isDuplicate(string $phrase): bool
    {
        return array_any(
            $this->service->getList(),
            static fn(Glossary $existing): bool => strcasecmp(trim((string) $existing->getPhrase()), $phrase) === 0,
        );
    }

    /** @param array<string, scalar|null> $payload */
    private function text(array $payload, string $field): string
    {
        return (string) ($payload[$field] ?? '');
    }

    /** @param array<string, scalar|null> $payload */
    private function optionalText(array $payload, string $field): ?string
    {
        $value = (string) ($payload[$field] ?? '');

        return $value === '' ? null : $value;
    }
}
