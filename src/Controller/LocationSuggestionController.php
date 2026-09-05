<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\ChangeProposal;
use App\Entity\Location;
use App\Entity\User;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use App\Review\ChangeProposalService;
use App\Review\FieldChange;
use App\Review\LocationChangeTarget;
use App\Service\Location\MemberVenueService;
use App\Suggestion\LocationTarget;
use App\Suggestion\SuggestionException;
use App\Suggestion\SuggestionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/locations')]
#[IsGranted('ROLE_USER')]
final class LocationSuggestionController extends AbstractController
{
    private const array PROPOSABLE_FIELDS = ['name', 'description', 'street', 'city', 'postcode', 'longitude', 'latitude'];

    public function __construct(
        private readonly SuggestionService $suggestionService,
        private readonly ChangeProposalService $changeProposalService,
        private readonly LocationRepository $locationRepo,
        private readonly MemberVenueService $memberVenueService,
    ) {}

    #[Route('', name: 'app_location_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('location/index.html.twig', $this->shell(null, null));
    }

    #[Route('/suggest', name: 'app_location_suggest', methods: ['GET', 'POST'])]
    public function suggest(Request $request, #[CurrentUser] User $user): Response
    {
        $form = $this->createForm(LocationType::class, $this->suggestionService->providerFor(LocationTarget::TARGET_TYPE)->newDraft());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->suggestionService->propose(LocationTarget::TARGET_TYPE, $user, $form->getData());
                $this->addFlash('success', 'location.flash_suggested');

                return $this->redirectToRoute('app_location_suggest');
            } catch (SuggestionException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('location/index.html.twig', $this->shell('suggest', null) + [
            'form' => $form,
            'pending' => $this->pendingSuggestionCards($user),
        ]);
    }

    #[Route('/{id}/propose', name: 'app_location_propose', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function propose(Request $request, int $id, #[CurrentUser] User $user): Response
    {
        $location = $this->locationRepo->find($id);
        $mayPropose = $this->changeProposalService->canProposeTarget(LocationChangeTarget::TARGET_TYPE, $id, $user);
        if (!$location instanceof Location || !$mayPropose) {
            throw $this->createNotFoundException();
        }

        $current = $this->fieldValues($location);
        $form = $this->createForm(LocationType::class, $this->detachedCopy($location));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $changes = $this->buildChanges($current, $this->fieldValues($form->getData()));
            $proposal = $this->changeProposalService->propose(LocationChangeTarget::TARGET_TYPE, $id, $user, $changes);
            $this->addFlash('success', $proposal === null ? 'location.flash_unchanged' : 'location.flash_proposed');

            return $this->redirectToRoute('app_location_propose', ['id' => $id]);
        }

        return $this->render('location/index.html.twig', $this->shell('propose', $id) + [
            'form' => $form,
            'location' => $location,
            'pendingProposals' => $this->pendingProposalCards($id),
        ]);
    }

    /**
     * @return array{venues: list<Location>, pane: ?string, activeVenueId: ?int}
     */
    private function shell(?string $pane, ?int $activeVenueId): array
    {
        return [
            'venues' => $this->memberVenueService->listForMember(),
            'pane' => $pane,
            'activeVenueId' => $activeVenueId,
        ];
    }

    /**
     * @return list<array{id: int, description: string, rows: list<array{label: string, value: string}>}>
     */
    private function pendingSuggestionCards(User $user): array
    {
        $cards = [];
        foreach ($this->suggestionService->pendingFor($user) as $suggestion) {
            if ($suggestion->getTargetType() !== LocationTarget::TARGET_TYPE) {
                continue;
            }

            $cards[] = [
                'id' => (int) $suggestion->getId(),
                'description' => $this->suggestionService->describe($suggestion),
                'rows' => $this->suggestionService->summaryRows($suggestion),
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{proposal: ChangeProposal, rows: list<array<string, mixed>>}>
     */
    private function pendingProposalCards(int $id): array
    {
        $cards = [];
        foreach ($this->changeProposalService->pendingForTarget(LocationChangeTarget::TARGET_TYPE, $id) as $proposal) {
            $cards[] = [
                'proposal' => $proposal,
                'rows' => $this->changeProposalService->fieldRows($proposal),
            ];
        }

        return $cards;
    }

    private function detachedCopy(Location $location): Location
    {
        $copy = new Location();
        $copy->setName((string) $location->getName());
        $copy->setDescription((string) $location->getDescription());
        $copy->setStreet((string) $location->getStreet());
        $copy->setCity((string) $location->getCity());
        $copy->setPostcode((string) $location->getPostcode());
        $copy->setLongitude($location->getLongitude());
        $copy->setLatitude($location->getLatitude());

        return $copy;
    }

    /**
     * @return array<string, ?string>
     */
    private function fieldValues(Location $location): array
    {
        return [
            'name' => $location->getName(),
            'description' => $location->getDescription(),
            'street' => $location->getStreet(),
            'city' => $location->getCity(),
            'postcode' => $location->getPostcode(),
            'longitude' => $location->getLongitude(),
            'latitude' => $location->getLatitude(),
        ];
    }

    /**
     * @param array<string, ?string> $before
     * @param array<string, ?string> $after
     *
     * @return list<FieldChange>
     */
    private function buildChanges(array $before, array $after): array
    {
        $changes = [];
        foreach (self::PROPOSABLE_FIELDS as $field) {
            $changes[] = new FieldChange($field, $before[$field] ?? null, $after[$field] ?? null);
        }

        return $changes;
    }
}
