<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use App\Entity\AbstractUpdate;
use App\Entity\User;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
                        new NotNull(message: 'admin.form.abstractupdate.create.titleen.null'),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.abstractupdate.create.titleen.too_long'),
                    ],
                    'label'       => 'admin.form.abstractupdate.create.titleen.label',
                    'help'        => 'admin.form.abstractupdate.create.titleen.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.abstractupdate.create.titleen.placeholder',
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
                        new NotNull(message: 'admin.form.abstractupdate.create.descriptionen.null'),
                    ],
                    'label'       => 'admin.form.abstractupdate.create.descriptionen.label',
                    'help'        => 'admin.form.abstractupdate.create.descriptionen.help',
                    'help_html'   => true,
                ]
            )
            ->add(
                'rulesModel',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstractupdate.create.rulesmodel.null'),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.abstractupdate.create.rulesmodel.too_long'),
                    ],
                    'label'       => 'admin.form.abstractupdate.create.rulesmodel.label',
                    'help'        => 'admin.form.abstractupdate.create.rulesmodel.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.abstractupdate.create.rulesmodel.placeholder',
                    ],
                ]
            )
            ->add(
                'isPublic',
                CheckboxType::class,
                [
                    'required' => false,
                    'label'    => 'admin.form.abstractupdate.create.ispublic.label',
                    'help'     => 'admin.form.abstractupdate.create.ispublic.help',
                ]
            )
            ->add(
                'startingAt',
                DateTimeType::class,
                [
                    'widget'      => 'single_text',
                    'required'    => true,
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstractupdate.create.startingat.null'),
                    ],
                    'label'       => 'admin.form.abstractupdate.create.startingat.label',
                    'help'        => 'admin.form.abstractupdate.create.startingat.help',
                ]
            )
            ->add(
                'endingAt',
                DateTimeType::class,
                [
                    'widget'      => 'single_text',
                    'required'    => true,
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstractupdate.create.endingat.null'),
                    ],
                    'label'       => 'admin.form.abstractupdate.create.endingat.label',
                    'help'        => 'admin.form.abstractupdate.create.endingat.help',
                ]
            )
            ->add(
                'user',
                EntityType::class,
                [
                    'class'       => User::class,
                    'required'    => false,
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstractupdate.create.user.null'),
                    ],
                    'label'       => 'admin.form.abstractupdate.create.user.label',
                    'help'        => 'admin.form.abstractupdate.create.user.help',
                    'placeholder' => 'admin.form.abstractupdate.create.user.placeholder',
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
