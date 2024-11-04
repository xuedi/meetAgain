<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    public function __construct(readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('email')
            ->add(
                'roles', ChoiceType::class, [
                    'choices' => [
                        $this->translator->trans('role_system') => 'ROLE_SYSTEM',
                        $this->translator->trans('role_admin') => 'ROLE_ADMIN',
                        $this->translator->trans('role_manager') => 'ROLE_MANAGER',
                        $this->translator->trans('role_user') => 'ROLE_USER',
                    ],
                    'expanded' => true,
                    'multiple' => true,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
