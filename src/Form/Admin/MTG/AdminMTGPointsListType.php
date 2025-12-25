<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

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
                        new NotNull(),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.mtg.points_list.create.title.too_long'),
                    ],
                    'label'       => 'admin.form.mtg.points_list.create.title.label',
                    'help'        => 'admin.form.mtg.points_list.create.title.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.mtg.points_list.create.title.placeholder',
                    ],
                ]
            )
            ->add(
                'validityStartingAt',
                DateTimeType::class,
                [
                    'widget'      => 'single_text',
                    'required'    => true,
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtg.points_list.create.validitystartingat.empty'),
                    ],
                    'label'       => 'admin.form.mtg.points_list.create.validitystartingat.label',
                    'help'        => 'admin.form.mtg.points_list.create.validitystartingat.help',
                ]
            )
            ->add(
                'rulesModel',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new NotNull(message: 'admin.form.abstract.update.create.rulesmodel.null'),
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.abstract.update.create.rulesmodel.too_long'),
                    ],
                    'label'       => 'admin.form.abstract.update.create.rulesmodel.label',
                    'help'        => 'admin.form.abstract.update.create.rulesmodel.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.abstract.update.create.rulesmodel.placeholder',
                    ],
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
