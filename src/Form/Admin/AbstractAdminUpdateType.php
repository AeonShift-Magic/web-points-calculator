<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use App\Entity\AbstractUpdate;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

class AbstractAdminUpdateType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'titleEN',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstract.update.create.titleen.null'),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.abstract.update.create.titleen.too_long'),
                    ],
                    'label'       => 'admin.form.abstract.update.create.titleen.label',
                    'help'        => 'admin.form.abstract.update.create.titleen.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.abstract.update.create.titleen.placeholder',
                    ],
                ]
            )
            ->add(
                'descriptionEN',
                CKEditorType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstract.update.create.descriptionen.null'),
                    ],
                    'label'       => 'admin.form.abstract.update.create.descriptionen.label',
                    'help'        => 'admin.form.abstract.update.create.descriptionen.help',
                    'help_html'   => true,
                ]
            )
            ->add(
                'isPublic',
                CheckboxType::class,
                [
                    'required' => false,
                    'label'    => 'admin.form.abstract.update.create.public.label',
                    'help'     => 'admin.form.abstract.update.create.public.help',
                ]
            )
            ->add(
                'startingAt',
                DateTimeType::class,
                [
                    'widget'      => 'single_text',
                    'required'    => true,
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstract.update.create.startingat.null'),
                    ],
                    'label'       => 'admin.form.abstract.update.create.startingat.label',
                    'help'        => 'admin.form.abstract.update.create.startingat.help',
                ]
            )
            ->add(
                'endingAt',
                DateTimeType::class,
                [
                    'widget'      => 'single_text',
                    'required'    => true,
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstract.update.create.endingat.null'),
                    ],
                    'label'       => 'admin.form.abstract.update.create.endingat.label',
                    'help'        => 'admin.form.abstract.update.create.endingat.help',
                ]
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AbstractUpdate::class,
        ]);
    }
}
