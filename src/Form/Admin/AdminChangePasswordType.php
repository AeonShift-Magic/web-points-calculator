<?php

declare(strict_types = 1);

namespace App\Form\Admin;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Regex;

final class AdminChangePasswordType extends AbstractType
{
    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type'    => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'first_options' => [
                    'attr'  => [
                        'minlength' => '8',
                    ],
                    'constraints' => [
                        new NotBlank(
                            message: 'front.user.forms.password.blank',
                        ),
                        new Regex(
                            pattern: '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/',
                            message: 'front.users.forms.password.help',
                        ),
                        new PasswordStrength(
                            minScore: PasswordStrength::STRENGTH_WEAK,
                        ),
                        new NotCompromisedPassword(),
                    ],
                    'label' => 'front.users.forms.password.first',
                    'help'  => 'front.users.forms.password.help',
                ],
                'second_options' => [
                    'label' => 'front.users.forms.password.second',
                    'attr'  => [
                        'minlength' => '8',
                    ],
                    'help'  => 'front.users.forms.password.help',
                ],
                'invalid_message' => 'front.users.forms.password.invalid_repeated',
                'help'            => 'front.users.forms.password.help',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
