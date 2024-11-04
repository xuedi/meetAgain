<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Event;
use App\Entity\EventIntervals;
use App\Entity\Host;
use App\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('initial', HiddenType::class, [
                'data' => true,
            ])
            ->add('start', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('stop', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            //->add('recurringOf')
            ->add(
                'recurringRule', ChoiceType::class, [
                    'choices' => EventIntervals::cases(),
                    'label' => 'Recurring',
                    'expanded' => false,
                    'multiple' => false,
                ]
            )
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 16],
            ])
            ->add('name', TextType::class, [
                'label' => 'Titel'
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => 'name',
            ])
            ->add('host', EntityType::class, [
                'class' => Host::class,
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
