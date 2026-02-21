<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use App\Entity\Page;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

final class AdminPageType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $pageZones = Page::getPageZonesForForms();
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new Length(min: 0, max: 255),
                    ],
                    'label'       => 'admin.form.page.create.title.label',
                    'help'        => 'admin.form.page.create.title.help',
                ]
            )
            ->add(
                'contents',
                CKEditorType::class,
                [
                    'required'    => true,
                    'config_name' => 'image_config',
                    'empty_data'  => '',
                    'label'       => 'admin.form.page.create.contents.label',
                    'help'        => 'admin.form.page.create.contents.help',
                    'help_html'   => true,
                ]
            )
            ->add(
                'zone',
                ChoiceType::class,
                [
                    'choices'     => Page::getPageZonesForForms(),
                    'multiple'    => false,
                    'expanded'    => false,
                    'required'    => true,
                    'empty_data'  => array_pop($pageZones),
                    'constraints' => [
                        new Length(min: 0, max: 255),
                    ],
                    'label'       => 'admin.form.page.create.zone.label',
                    'help'        => 'admin.form.page.create.zone.help',
                ]
            )
            ->add(
                'language',
                LanguageType::class,
                [
                    'required'          => true,
                    'empty_data'        => '',
                    'constraints'       => [
                        new Length(min: 0, max: 20),
                    ],
                    'label'             => 'admin.form.page.create.language.label',
                    'help'              => 'admin.form.page.create.language.help',
                    'preferred_choices' => ['en', 'fr', 'de', 'it', 'es', 'pt', 'jp', 'zh', 'fil', 'cs'],
                ]
            )
            ->add(
                'weight',
                IntegerType::class,
                [
                    'empty_data'  => 0,
                    'constraints' => [
                        new Range(min: -100, max: 100),
                    ],
                    'label'       => 'admin.form.page.create.weight.label',
                    'help'        => 'admin.form.page.create.weight.help',
                ]
            )
            ->add('slug', TextType::class, [
                'empty_data'  => '',
                'required'    => false,
                'constraints' => [
                    new Length(min: 0, max: 255),
                ],
                'label'       => 'admin.form.page.create.slug.label',
                'help'        => 'admin.form.page.create.slug.help',
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
