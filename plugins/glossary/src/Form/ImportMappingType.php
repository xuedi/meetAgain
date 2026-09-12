<?php declare(strict_types=1);

namespace Plugin\Glossary\Form;

use Override;
use Plugin\Glossary\Enum\DuplicatePolicy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportMappingType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $columns = $options['columns'];
        $tags = $options['tags'];
        $column = [
            'choices' => array_keys($columns),
            'choice_label' => static fn(int $index): string => $columns[$index],
            'choice_translation_domain' => false,
        ];
        $optionalColumn = [...$column, 'required' => false, 'placeholder' => 'glossary_import.column_none'];

        $builder
            ->add('termColumn', ChoiceType::class, [...$column, 'label' => 'glossary_import.field_term_column'])
            ->add('definitionColumn', ChoiceType::class, [...$column, 'label' => 'glossary_import.field_definition_column'])
            ->add('secondaryColumn', ChoiceType::class, [...$optionalColumn, 'label' => 'glossary_import.field_secondary_column'])
            ->add('tagsColumn', ChoiceType::class, [...$optionalColumn, 'label' => 'glossary_import.field_tags_column'])
            ->add('language', ChoiceType::class, [
                'label' => 'glossary_import.field_language',
                'choices' => $options['languages'],
                'choice_label' => static fn(string $code): string => strtoupper($code),
                'choice_translation_domain' => false,
            ])
            ->add('targetTag', ChoiceType::class, [
                'label' => 'glossary_import.field_target_tag',
                'choices' => array_keys($tags),
                'choice_label' => static fn(int $id): string => $tags[$id],
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'glossary_import.target_none',
            ])
            ->add('newTagLabel', TextType::class, [
                'label' => 'glossary_import.field_new_tag',
                'help' => 'glossary_import.field_new_tag_help',
                'required' => false,
            ])
            ->add('duplicatePolicy', EnumType::class, [
                'class' => DuplicatePolicy::class,
                'label' => 'glossary_import.field_duplicates',
                'expanded' => true,
                'choice_label' => static fn(DuplicatePolicy $policy): string => $policy->label(),
            ])
            ->add('preview', SubmitType::class, [
                'label' => 'glossary_import.button_preview',
                'attr' => ['class' => 'button'],
            ])
            ->add('import', SubmitType::class, [
                'label' => 'glossary_import.button_import',
                'attr' => ['class' => 'button is-primary'],
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['columns', 'tags', 'languages']);
        $resolver->setAllowedTypes('columns', 'array');
        $resolver->setAllowedTypes('tags', 'array');
        $resolver->setAllowedTypes('languages', 'array');
        $resolver->setDefaults([
            'csrf_token_id' => 'glossary_import_map',
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'glossary_import_map';
    }
}
