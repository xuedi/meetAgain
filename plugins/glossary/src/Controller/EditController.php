<?php declare(strict_types=1);

namespace Plugin\Glossary\Controller;

use App\Item\Tag\AssignmentFormHelper;
use App\Review\ChangeProposalService;
use Plugin\Glossary\Form\GlossaryType;
use Plugin\Glossary\Item\GlossaryTaggableTypeProvider;
use Plugin\Glossary\Service\GlossaryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/glossary/edit')]
#[IsGranted('ROLE_ORGANIZER')]
final class EditController extends AbstractGlossaryController
{
    public function __construct(
        GlossaryService $service,
        private readonly ChangeProposalService $changeProposalService,
        private readonly AssignmentFormHelper $assignmentFormHelper,
    ) {
        parent::__construct($service);
    }

    #[Route('/{id}', name: 'app_plugin_glossary_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $newGlossary = $this->service->getManaged($id);
        if ($newGlossary === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(GlossaryType::class, $newGlossary);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->service->detach($newGlossary); // detach first: a managed entity would be flushed with the request's changes
            $this->service->update($newGlossary, $id, $this->assignmentFormHelper->extractAssignment($form));

            return $this->redirectToRoute('app_plugin_glossary');
        }

        $pendingProposals = [];
        foreach ($this->changeProposalService->pendingForTarget(GlossaryTaggableTypeProvider::ITEM_TYPE, $id) as $proposal) {
            $pendingProposals[] = [
                'proposal' => $proposal,
                'rows' => $this->changeProposalService->fieldRows($proposal),
            ];
        }

        return $this->renderPage('@Glossary/edit.html.twig', [
            'editItem' => $this->service->getManaged($id),
            'pendingProposals' => $pendingProposals,
            'form' => $form,
        ]);
    }
}
