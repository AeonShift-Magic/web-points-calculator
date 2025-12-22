<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\User;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

final class AdminMTGPointsListType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgpointslist.create.title.null'),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.mtgpointslist.create.title.too_long'),
                    ],
                    'label'       => 'admin.form.mtgpointslist.create.title.label',
                    'help'        => 'admin.form.mtgpointslist.create.title.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.mtgpointslist.create.title.placeholder',
                    ],
                ]
            )
            ->add(
                'filename',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgpointslist.create.filename.null'),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.mtgpointslist.create.filename.too_long'),
                    ],
                    'label'       => 'admin.form.mtgpointslist.create.filename.label',
                    'help'        => 'admin.form.mtgpointslist.create.filename.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.mtgpointslist.create.filename.placeholder',
                    ],
                ]
            )
            ->add(
                'nbCards',
                IntegerType::class,
                [
                    'required'    => true,
                    'empty_data'  => 0,
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgpointslist.create.nbcards.null'),
                        new Range(min: 0, max: 10000),
                    ],
                    'label'       => 'admin.form.mtgpointslist.create.nbcards.label',
                    'help'        => 'admin.form.mtgpointslist.create.nbcards.help',
                ]
            )
            ->add(
                'uploadedAt',
                DateTimeType::class,
                [
                    'widget'      => 'single_text',
                    'required'    => true,
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgpointslist.create.uploadedat.null'),
                    ],
                    'label'       => 'admin.form.mtgpointslist.create.uploadedat.label',
                    'help'        => 'admin.form.mtgpointslist.create.uploadedat.help',
                ]
            )
            ->add(
                'user',
                EntityType::class,
                [
                    'class'       => User::class,
                    'required'    => false,
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgpointslist.create.user.null'),
                    ],
                    'label'       => 'admin.form.mtgpointslist.create.user.label',
                    'help'        => 'admin.form.mtgpointslist.create.user.help',
                    'placeholder' => 'admin.form.mtgpointslist.create.user.placeholder',
                ]
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MTGPointsList::class,
        ]);
    }
}
