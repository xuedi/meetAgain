<?php declare(strict_types=1);

namespace Module\Ballot\Internal\Twig;

use App\Entity\User;
use Module\Ballot\Contract\BallotInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class BallotRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private BallotInterface $ballots,
        private Security $security,
        private Environment $twig,
    ) {}

    public function navbarPill(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return '';
        }

        $open = $this->ballots->countOpenFor((int) $user->getId());

        return $open === 0 ? '' : $this->twig->render('@Ballot/_navbar_pill.html.twig', ['open' => $open]);
    }
}
