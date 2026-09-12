<?php declare(strict_types=1);

namespace Plugin\Glossary\Form;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ImportUploadType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label' => 'glossary_import.field_file',
            'help' => 'glossary_import.field_file_help',
            'constraints' => [
                new NotNull(message: 'glossary_import.validator_file_missing'),
                new File(
                    maxSize: '5M',
                    maxSizeMessage: 'glossary_import.validator_file_size',
                    extensions: [
                        'txt' => ['text/plain'],
                        'csv' => ['text/csv', 'text/plain', 'application/csv', 'text/x-csv'],
                        'tsv' => ['text/tab-separated-values', 'text/plain'],
                    ],
                    extensionsMessage: 'glossary_import.validator_file_type',
                ),
            ],
        ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'glossary_import_upload',
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'glossary_import_upload';
    }
}
