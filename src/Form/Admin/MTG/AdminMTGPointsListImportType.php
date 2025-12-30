<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

final class AdminMTGPointsListImportType extends AbstractType implements AdminMTGFormTypeInterface
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'csv_file',
                FileType::class,
                [
                    'label'       => 'admin.form.mtg.pointslist.import.file.label',
                    'required'    => true,
                    'help'        => 'admin.form.mtg.pointslist.import.file.help',
                    'help_html'   => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new File(
                            maxSize: '8192k',
                            extensions: [
                                'csv' => [
                                    'text/csv',
                                    'application/csv',
                                    'text/x-comma-separated-values',
                                    'text/x-csv',
                                    'text/plain',
                                ],
                            ],
                            extensionsMessage: 'admin.form.mtg.pointslist.import.file.error',
                        ),
                    ],
                ]
            )
            ->add(
                'confirm',
                CheckboxType::class,
                [
                    'label'      => 'admin.form.mtg.pointslist.import.confirm.label',
                    'required'   => true,
                    'empty_data' => false,
                ]
            );
    }
}
