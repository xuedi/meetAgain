<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventIntervals;
use App\Entity\EventTranslation;
use App\Entity\EventTypes;
use App\Entity\Host;
use App\Entity\Location;
use App\Repository\EventTranslationRepository;
use App\Service\TranslationService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TranslationService $translationService,
        private readonly EventTranslationRepository $eventTransRepo,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $event = $options['data'] ?? null;
        $builder
            ->add('initial', HiddenType::class, [
                'data' => true,
            ])
            ->add('published', ChoiceType::class, [
                'label' => 'Status',
                'choices'  => [
                    $this->translator->trans('published') => true,
                    $this->translator->trans('draft') => false,
                ],
            ])
            ->add('featured', ChoiceType::class, [
                'label' => 'Featured',
                'choices'  => [
                    $this->translator->trans('yes') => true,
                    $this->translator->trans('no') => false,
                ],
            ])
            ->add('start', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('stop', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('recurringRule', EnumType::class, [
                'class' => EventIntervals::class,
                'label' => 'Recurring',
                'placeholder' => 'NonRecurring',
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'disabled' => ($event?->getRecurringOf() !== null) ? true : false,
            ])
            ->add('type', EnumType::class, [
                'class' => EventTypes::class,
                'label' => 'Type',
                'placeholder' => 'Types',
                'required' => false,
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
            ])
            ->add('host', EntityType::class, [
                'class' => Host::class,
                'choice_label' => 'name',
                'label' => 'Hosts',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('image', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Preview Image',
                'constraints' => [
                    new File([
                        'maxSize' => '5000k',
                        'mimeTypes' => [
                            'image/*',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image, preferable 16x9 format',
                    ])
                ],
            ])->add('allFollowing', ChoiceType::class, [
                'label' => 'Also update all following events',
                'data' => false,
                'mapped' => false,
                'choices'  => [
                    $this->translator->trans('yes') => true,
                    $this->translator->trans('no') => false,
                ],
            ]);

        $eventId = $event?->getId();
        if (null !== $eventId) { // not for new events
            foreach ($this->translationService->getLanguageCodes() as $languageCode) {
                $translation = $this->eventTransRepo->findOneBy(['event' => $eventId, 'language' => $languageCode]);
                $builder->add("title-$languageCode", TextType::class, [
                    'label' => "title ($languageCode)",
                    'data' => $translation?->getTitle() ?? '',
                    'mapped' => false,
                ]);
                $builder->add("description-$languageCode", TextareaType::class, [
                    'label' => "description ($languageCode)",
                    'data' => $translation?->getDescription() ?? '',
                    'mapped' => false,
                    'attr' => ['rows' => 15],
                ]);
                $builder->add("teaser-$languageCode", TextareaType::class, [
                    'label' => "Teaser ($languageCode)",
                    'data' => $translation?->getTeaser() ?? '',
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['rows' => 2],
                ]);
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
