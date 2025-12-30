<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListCard;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class AdminMTGPointsListCardType extends AdminAbstractCardType implements AdminMTGFormTypeInterface
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'pointsList',
                EntityType::class,
                [
                    'class'       => MTGPointsList::class,
                    'required'    => false,
                    'constraints' => [
                        new NotNull(),
                    ],
                    'label'       => 'admin.form.mtg.pointslistcard.create.pointslist.label',
                    'help'        => 'admin.form.mtg.pointslistcard.create.pointslist.help',
                    'placeholder' => 'admin.form.mtg.pointslistcard.create.pointslist.placeholder',
                ]
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MTGPointsListCard::class,
        ]);
    }
}
