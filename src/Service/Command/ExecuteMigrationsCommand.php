<?php declare(strict_types=1);

namespace App\Service\Command;

readonly class ExecuteMigrationsCommand implements CommandInterface
{
    public function getCommand(): string
    {
        return 'doctrine:migrations:migrate';
    }

    public function getParameter(): array
    {
        return [
            'command' => $this->getCommand(),
            '--no-interaction' => true,
        ];
    }
}
