<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Model\AeonShift\PointsListModelDetectorModel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

final class AdminMTGPointsListType extends AbstractType implements AdminMTGFormTypeInterface
{
    public function __construct(private PointsListModelDetectorModel $pointsListModelDetectorModel)
    {
    }

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
                        new Length(min: 0, max: 255, maxMessage: 'admin.form.mtg.pointslist.create.title.too_long'),
                    ],
                    'label'       => 'admin.form.mtg.pointslist.create.title.label',
                    'help'        => 'admin.form.mtg.pointslist.create.title.help',
                    'attr'        => [
                        'placeholder' => 'admin.form.mtg.pointslist.create.title.placeholder',
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
                        new NotNull(message: 'admin.form.mtg.pointslist.create.validitystartingat.empty'),
                    ],
                    'label'       => 'admin.form.mtg.pointslist.create.validitystartingat.label',
                    'help'        => 'admin.form.mtg.pointslist.create.validitystartingat.help',
                ]
            )
            ->add(
                'rulesModel',
                ChoiceType::class,
                [
                    'required'                  => true,
                    'empty_data'                => '',
                    'choices'                   => $this->pointsListModelDetectorModel->getPointsListModelsForForms(self::LICENCE),
                    'constraints'               => [
                        new NotNull(),
                    ],
                    'choice_translation_domain' => false,
                    'label'                     => 'admin.form.abstract.update.create.rulesmodel.label',
                    'help'                      => 'admin.form.abstract.update.create.rulesmodel.help',
                ]
            )
            ->add(
                'mValueFactor0',
                NumberType::class,
                [
                    'required'           => true,
                    'empty_data'         => 0.0,
                    'scale'              => 8,
                    'label'              => 'M-Value Factor 0',
                    'help'               => 'x^0',
                    'translation_domain' => false,
                    'constraints'        => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'mValueFactor1',
                NumberType::class,
                [
                    'required'    => true,
                    'empty_data'  => 0.0,
                    'label'       => 'M-Value Factor 1',
                    'help'        => 'x^1',
                    'scale'       => 8,
                    'constraints' => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'mValueFactor2',
                NumberType::class,
                [
                    'required'    => true,
                    'empty_data'  => 0.0,
                    'label'       => 'M-Value Factor 2',
                    'help'        => 'x^2',
                    'scale'       => 8,
                    'constraints' => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'mValueShippingFloor',
                NumberType::class,
                [
                    'required'    => true,
                    'empty_data'  => 50.0,
                    'label'       => 'M-Value Shipping Floor',
                    'help'        => 'Min value for shipping added price.',
                    'scale'       => 8,
                    'constraints' => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'mValueShippingCeiling',
                NumberType::class,
                [
                    'required'    => true,
                    'empty_data'  => 0.1,
                    'label'       => 'M-Value Shipping Ceiling',
                    'help'        => 'Max value for shipping added price.',
                    'scale'       => 8,
                    'constraints' => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'mValueShippingMultiplier',
                NumberType::class,
                [
                    'required'    => true,
                    'empty_data'  => 1.2,
                    'label'       => 'M-Value Shipping Multiplier',
                    'help'        => 'Multiplier value for shipping added price ([Base M-Value] * [This multiplier] = [Final Price For Financial Points]).',
                    'scale'       => 8,
                    'constraints' => [
                        new NotNull(),
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
