<?php declare(strict_types=1);

namespace Plugin\Glossary\Activity\Messages;

use App\Activity\MessageAbstract;

class EntriesImported extends MessageAbstract
{
    public const string TYPE = 'glossary.entries_imported';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function validate(): MessageAbstract
    {
        foreach (['created', 'updated', 'skipped'] as $key) {
            $this->ensureHasKey($key);
            $this->ensureIsNumeric($key);
        }

        return $this;
    }

    protected function renderText(): string
    {
        return $this->translator->trans('glossary_import.activity_imported', [
            '%created%' => (int) $this->meta['created'],
            '%updated%' => (int) $this->meta['updated'],
            '%skipped%' => (int) $this->meta['skipped'],
        ]);
    }

    protected function renderHtml(): string
    {
        return $this->escapeHtml($this->renderText());
    }
}
