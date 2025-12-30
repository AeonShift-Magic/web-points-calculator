<?php

declare(strict_types = 1);

namespace App\Form\Admin\MTG;

use App\Entity\MTG\MTGAbstractCard;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Uuid;

abstract class AdminAbstractCardType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nameEN', TextType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.nameen.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.nameen.help',
                'constraints' => [
                    new NotNull(),
                    new Length(max: 255),
                ],
            ])
            ->add('scryfallId', TextType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.scryfallid.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.scryfallid.help',
                'required'    => false,
                'constraints' => [
                    new Uuid(),
                ],
            ])
            ->add('scryfallURI', UrlType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.scryfalluri.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.scryfalluri.help',
                'required'    => false,
                'empty_data'  => '',
                'constraints' => [
                    new NotNull(),
                    new Length(max: 255),
                ],
            ])
            ->add('oracleId', TextType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.oracleid.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.oracleid.help',
                'required'    => false,
                'constraints' => [
                    new Uuid(),
                ],
            ])
            ->add('manaValue', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.manavalue.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.manavalue.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('multiCZType', TextType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.multicztype.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.multicztype.help',
                'required'    => false,
                'empty_data'  => '',
                'constraints' => [
                    new NotNull(),
                    new Length(max: 255),
                ],
            ])
            ->add('points2HG', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.points2hg.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.points2hg.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('points2HGSpecial', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.points2hgspecial.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.points2hgspecial.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsDuel', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsduel.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsduel.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsDuelSpecial', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsduelspecial.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsduelspecial.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsMulti', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsmulti.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsmulti.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsMultiSpecial', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsmultispecial.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsmultispecial.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('isLegal2HG', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegal2hg.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegal2hg.help',
                'required' => false,
            ])
            ->add('isLegal2HGSpecial', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegal2hgspecial.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegal2hgspecial.help',
                'required' => false,
            ])
            ->add('isLegalDuel', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalduel.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalduel.help',
                'required' => false,
            ])
            ->add('isLegalDuelSpecial', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalduelspecial.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalduelspecial.help',
                'required' => false,
            ])
            ->add('isLegalMulti', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalmulti.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalmulti.help',
                'required' => false,
            ])
            ->add('isLegalMultiSpecial', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalmultispecial.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalmultispecial.help',
                'required' => false,
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MTGAbstractCard::class,
        ]);
    }
}
