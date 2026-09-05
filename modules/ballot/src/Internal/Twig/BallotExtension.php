<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Twig;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class BallotExtension extends AbstractExtension
{
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ballot_navbar_pill', [BallotRuntime::class, 'navbarPill'], ['is_safe' => ['html']]),
        ];
    }
}
