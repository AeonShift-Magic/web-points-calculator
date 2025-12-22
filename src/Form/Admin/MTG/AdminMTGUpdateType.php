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
        $builder
            ->add(
                'pointsList',
                EntityType::class,
                [
                    'class'       => MTGPointsList::class,
                    'required'    => false,
                    'constraints' => [
                        new NotNull(message: 'admin.form.mtgupdate.create.pointslist.null'),
                    ],
                    'label'       => 'admin.form.mtgupdate.create.pointslist.label',
                    'help'        => 'admin.form.mtgupdate.create.pointslist.help',
                    'placeholder' => 'admin.form.mtgupdate.create.pointslist.placeholder',
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
