<?php declare(strict_types=1);

namespace App\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventCorrectionType extends AbstractType
{
    private const string LONG_TEXT_PROPERTY = 'description';

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var array<string, string> $fields */
        $fields = $options['fields'];

        foreach ($fields as $field => $label) {
            $isLongText = str_starts_with($field, self::LONG_TEXT_PROPERTY);
            $builder->add($field, $isLongText ? TextareaType::class : TextType::class, [
                'label' => $label,
                'required' => false,
            ]);
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['fields' => [], 'data_class' => null]);
        $resolver->setAllowedTypes('fields', 'array');
    }
}
