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
            ->add('pointsBaseSingleton', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsbasesingleton.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsbasesingleton.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsBaseQuadruples', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsbasequadruples.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsbasequadruples.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
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
            ->add('pointsDuelCommander', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsduelcommander.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsduelcommander.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsDuelCommanderSpecial', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsduelcommanderspecial.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsduelcommanderspecial.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsCommander', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointscommander.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointscommander.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsCommanderSpecial', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointscommanderspecial.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointscommanderspecial.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsHighlander', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointshighlander.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointshighlander.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsModern', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsmodern.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsmodern.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsPioneer', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointspioneer.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointspioneer.help',
                'scale'       => 2,
                'constraints' => [
                    new NotNull(),
                ],
            ])
            ->add('pointsStandard', NumberType::class, [
                'label'       => 'admin.form.mtg.pointslistcard.create.pointsstandard.label',
                'help'        => 'admin.form.mtg.pointslistcard.create.pointsstandard.help',
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
            ->add('isLegalDuelCommander', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalduelcommander.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalduelcommander.help',
                'required' => false,
            ])
            ->add('isLegalDuelCommanderSpecial', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalduelcommanderspecial.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalduelcommanderspecial.help',
                'required' => false,
            ])
            ->add('isLegalCommander', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalcommander.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalcommander.help',
                'required' => false,
            ])
            ->add('isLegalCommanderSpecial', CheckboxType::class, [
                'label'    => 'admin.form.mtg.pointslistcard.create.islegalcommanderspecial.label',
                'help'     => 'admin.form.mtg.pointslistcard.create.islegalcommanderspecial.help',
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
