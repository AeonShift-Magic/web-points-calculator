<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use App\Entity\CustomBlock;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

final class AdminCustomBlockType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'blockKey',
                ChoiceType::class,
                [
                    'choices'     => CustomBlock::getCustomBlockKeysForForms(),
                    'expanded'    => false,
                    'multiple'    => false,
                    'required'    => true,
                    'constraints' => [
                        new NotBlank(),
                        new NotNull(),
                    ],
                    'label'       => 'admin.form.customblock.create.blockkey.label',
                    'help'        => 'admin.form.customblock.create.blockkey.help',
                    'placeholder' => 'admin.form.customblock.create.blockkey.placeholder',
                ]
            )
            ->add(
                'contents',
                CKEditorType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotBlank(),
                        new NotNull(),
                    ],
                    'label'       => 'admin.form.customblock.create.contents.label',
                    'help'        => 'admin.form.customblock.create.contents.help',
                    'help_html'   => true,
                    'config_name' => 'image_config',
                ]
            )
            ->add(
                'weight',
                IntegerType::class,
                [
                    'required'    => true,
                    'empty_data'  => 0,
                    'constraints' => [
                        new NotNull(),
                        new Range(
                            min: -100,
                            max: 100
                        ),
                    ],
                    'label'       => 'admin.form.customblock.create.weight.label',
                    'help'        => 'admin.form.customblock.create.weight.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.customblock.create.weight.placeholder',
                    ],
                ]
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomBlock::class,
        ]);
    }
}
