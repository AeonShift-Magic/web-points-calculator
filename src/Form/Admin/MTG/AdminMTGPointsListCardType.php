<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGPointsListCard;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class AdminMTGPointsListCardType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'list',
                EntityType::class,
                [
                    'class'       => MTGPointsList::class,
                    'required'    => false,
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgpointslistcard.create.list.null'),
                    ],
                    'label'       => 'admin.form.mtgpointslistcard.create.list.label',
                    'help'        => 'admin.form.mtgpointslistcard.create.list.help',
                    'placeholder' => 'admin.form.mtgpointslistcard.create.list.placeholder',
                ]
            );
        // Additional fields for MTGAbstractCard will be inherited automatically or added separately
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MTGPointsListCard::class,
        ]);
    }
}
