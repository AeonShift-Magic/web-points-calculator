<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Model\AeonShift\PointsListModelDetectorModel;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
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
                    'required'    => true,
                    'empty_data'  => '',
                    'choices'     => $this->pointsListModelDetectorModel->getPointsListModelsForForms(self::LICENCE),
                    'constraints' => [
                        new NotNull(),
                    ],
                    'choice_translation_domain' => false,
                    'label'                     => 'admin.form.abstract.update.create.rulesmodel.label',
                    'help'                      => 'admin.form.abstract.update.create.rulesmodel.help',
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
