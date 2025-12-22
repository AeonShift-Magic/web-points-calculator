<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use App\Entity\User;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;

final class AdminUserType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new Length(min: 0, max: 180),
                    ],
                    'label'       => 'admin.account.username.label',
                    'help'        => 'admin.account.username.help',
                    'attr'        => [
                        'autocomplete' => 'username',
                        'placeholder'  => 'admin.account.username.placeholder',
                    ],
                ]
            )
            ->add(
                'roles',
                ChoiceType::class,
                [
                    'choices'     => User::ROLES,
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'account.roles.label',
                    'help'        => 'account.roles.help',
                    'constraints' => [
                        new Choice(
                            choices: User::ROLES,
                            multiple: true,
                            message: 'admin.form.user.create.roles.invalid',
                            multipleMessage: 'admin.form.user.create.roles.multipleinvalid',
                        ),
                    ],
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'required'    => true,
                    'empty_data'  => '',
                    'constraints' => [
                        new Length(min: 0, max: 255),
                    ],
                    'label'       => 'admin.account.email.label',
                    'help'        => 'admin.account.email.help',
                    'help_html'   => true,
                    'attr'        => [
                        'autocomplete' => 'email',
                        'placeholder'  => 'admin.account.email.placeholder',
                    ],
                ]
            );

    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
