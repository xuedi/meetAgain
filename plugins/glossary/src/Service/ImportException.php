<?php declare(strict_types=1);

namespace Plugin\Glossary\Service;

use RuntimeException;

final class ImportException extends RuntimeException
{
    /** @param array<string, int|string> $parameters */
    public function __construct(
        string $messageKey,
        public readonly array $parameters = [],
    ) {
        parent::__construct($messageKey);
    }
}
