<?php declare(strict_types=1);

namespace Plugin\Dishes\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\Dishes\Entity\Dish;
use Plugin\Dishes\Entity\ViewType;
use Plugin\Dishes\Form\DishType;
use Plugin\Dishes\Repository\DishRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/dishes')]
class IndexController extends AbstractController
{
    public function __construct(
        private readonly DishRepository $repo,
        private readonly EntityManagerInterface $em
    )
    {
    }

    #[Route('', name: 'app_plugin_dishes', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $session = $request->getSession();

        return $this->render('@Dishes/index.html.twig', [
            'list' => $this->repo->findAll(),
            'viewType' => $session->get('dishesViewType', ViewType::Tiles->value),
            'viewTypeList' => [
                ViewType::List->value => 'list',
                ViewType::Tiles->value => 'grip',
                ViewType::Grid->value => 'table-cells',
                ViewType::Gallery->value => 'images'
            ],
        ]);
    }

    #[Route('/view/{id}', name: 'plugin_dishes_item_show', methods: ['GET'])]
    public function view(int $id): Response
    {
        return $this->render('@Dishes/details.html.twig', [
            'dish' => $this->repo->findOneBy(['id' => $id]),
        ]);
    }

    #[Route('/filter/{name}/set/{value}', name: 'plugin_dishes_filter', methods: ['GET'])]
    public function filter(ViewType $view): Response
    {
        // save settings to session and display
        return $this->redirectToRoute('app_plugin_dishes');
    }

    #[Route('/set/view/{type}', name: 'plugin_dishes_set_view_type', methods: ['GET'])]
    public function setViewType(Request $request, ViewType $type): Response
    {
        $session = $request->getSession();
        $session->set('dishesViewType', $type->value);

        return $this->redirectToRoute('app_plugin_dishes');
    }

    #[Route('/edit/{id}', name: 'app_plugin_dishes_edit')]
    public function edit(Request $request, Dish $dish, ?int $id = null): Response
    {
        if ($id !== null) {
            $dish = $this->repo->findOneBy(['id' => $id]);
        }
        $form = $this->createForm(DishType::class, $dish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->getUser() instanceof User) {
                throw new AuthenticationException('Only for logged in users');
            }
            $dish->setCreatedBy($this->getUser()->getId());
            $dish->setCreatedAt(new DateTimeImmutable());
            $dish->setApproved(false);

            $this->em->persist($dish);
            $this->em->flush();

            return $this->redirectToRoute('app_plugin_dishes');
        }

        return $this->render('@Dishes/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
