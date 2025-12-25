<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGPointsList;
use App\Entity\MTG\MTGUpdate;
use App\Form\Admin\AbstractAdminUpdateType;
use Override;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class AdminMTGUpdateType extends AbstractAdminUpdateType
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
                        new NotNull(message: 'admin.form.abstract.update.create.pointslist.empty'),
                    ],
                    'label'        => 'admin.form.abstract.update.create.pointslist.label',
                    'help'         => 'admin.form.abstract.update.create.pointslist.help',
                    'placeholder'  => 'admin.form.abstract.update.create.pointslist.placeholder',
                    'choice_label' => static function (MTGPointsList $MTGPointsList): string {
                        return (string)$MTGPointsList;
                    },
                ]
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MTGUpdate::class,
        ]);
    }
}
