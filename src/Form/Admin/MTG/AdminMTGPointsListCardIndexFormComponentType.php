<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

final class AdminMTGPointsListCardIndexFormComponentType extends AbstractType implements AdminMTGFormTypeInterface
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameEN', TextType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.nameen.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.nameen.help',
                'required'    => false,
                'empty_data'  => '',
                'constraints' => [
                    new Length(max: 255),
                ],
            ])
            ->add(
                'pointsList',
                EntityType::class,
                [
                    'class'       => MTGPointsList::class,
                    'required'    => false,
                    'label'       => 'admin.form.mtg.pointslistcard.create.pointslist.label',
                    'help'        => 'admin.form.mtg.pointslistcard.create.pointslist.help',
                    'placeholder' => 'admin.form.mtg.pointslistcard.create.pointslist.placeholder',
                ]
            );
    }
}
