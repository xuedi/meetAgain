<?php declare(strict_types=1);

namespace App\Service\Activity;

use InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;

abstract class MessageAbstract implements MessageInterface
{
    protected RouterInterface $router;
    protected ?array $meta = [];
    protected array $userNames = [];
    protected array $eventNames = [];

    public function injectServices(
        RouterInterface $router,
        ?array $meta = [],
        array $userNames = [],
        array $eventNames = [],
    ): self {
        $this->router = $router;
        $this->meta = $meta;
        $this->userNames = $userNames;
        $this->eventNames = $eventNames;

        return $this;
    }

    abstract protected function renderText(): string;

    abstract protected function renderHtml(): string;

    public function render(bool $asHtml = false): string
    {
        return $asHtml ? $this->renderHtml() : $this->renderText();
    }

    protected function ensureHasKey(string $key): void
    {
        if (!isset($this->meta[$key])) {
            throw new InvalidArgumentException(sprintf(
                "Missing '%s' in meta in %s",
                $key,
                $this->getType()->name
            ));
        }
    }

    protected function ensureIsNumeric(string $key): void
    {
        if (!is_numeric($this->meta[$key])) {
            throw new InvalidArgumentException(sprintf(
                "Value '%s' has to be numeric in '%s'",
                $key,
                $this->getType()->name
            ));
        }
    }
}
