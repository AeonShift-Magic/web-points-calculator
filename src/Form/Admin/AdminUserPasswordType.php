<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use App\Entity\User;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

final class AdminUserPasswordType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'password',
                RepeatedType::class,
                [
                    'type'            => PasswordType::class,
                    'required'        => true,
                    'empty_data'      => '',
                    'options'         => ['attr' => ['class' => 'password-field']],
                    'first_options'   => [
                        'label' => 'admin.user.forms.password.first',
                        'attr'  => [
                            'minlength' => '8',
                        ],
                        'help'  => 'admin.user.forms.password.help',
                    ],
                    'second_options'  => [
                        'label' => 'admin.user.forms.password.second',
                        'attr'  => [
                            'minlength' => '8',
                        ],
                        'help'  => 'admin.user.forms.password.help',
                    ],
                    'invalid_message' => 'admin.user.forms.password.invalid_repeated',
                    'help'            => 'admin.user.forms.password.help',
                    'constraints'     => [
                        new Length(
                            min: 8,
                        ),
                        new Regex(
                            pattern: '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/',
                        ),
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
