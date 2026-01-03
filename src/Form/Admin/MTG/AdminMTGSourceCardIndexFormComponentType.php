<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

final class AdminMTGSourceCardIndexFormComponentType extends AbstractType implements AdminMTGFormTypeInterface
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameEN', TextType::class, [
                'label'       => 'admin.form.mtg.sourcecard.create.nameen.label',
                'help'        => 'admin.form.mtg.sourcecard.create.nameen.help',
                'attr'        => [
                    'placeholder' => 'admin.form.mtg.sourcecard.create.nameen.placeholder',
                ],
                'required'    => false,
                'empty_data'  => '',
                'constraints' => [
                    new Length(max: 255),
                ],
            ])
            ->add('isCommandZoneEligible', ChoiceType::class, [
                'label'       => 'admin.form.mtg.sourcecard.create.iscommandzoneeligible.label',
                'help'        => 'admin.form.mtg.sourcecard.create.iscommandzoneeligible.help',
                'required'    => false,
                'placeholder' => 'global.choose.label',
                'choices'     => [
                    'global.1.label'  => '1',
                    'global.0.label'  => '0',
                ],
            ]);
    }
}
