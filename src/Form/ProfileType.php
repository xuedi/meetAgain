<?php declare(strict_types=1);

namespace App\Form;

use App\Entity\Host;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('image', FileType::class, [
            'mapped' => false,
            'required' => false,
            'label' => false,
            'constraints' => [
                new File([
                    'maxSize' => '3000k',
                    'mimeTypes' => [
                        'image/*',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid image, preferable a square format',
                ])
            ],
        ])->add(
            'languages', ChoiceType::class, [
                'mapped' => false,
                'label' => false,
                'choices' => [
                    'a' => 'ROLE_SYSTEM',
                    'b' => 'ROLE_SYSTEM',
                ],
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
