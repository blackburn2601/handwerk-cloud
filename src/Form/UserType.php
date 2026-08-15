<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, ['label' => 'Vorname'])
            ->add('surname', TextType::class, ['label' => 'Nachname'])
            ->add('email', EmailType::class, ['label' => 'E-Mail'])
            ->add('roles', ChoiceType::class, [
                'label' => 'Gruppen',
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Benutzer' => 'ROLE_USER',
                    'Administrator' => 'ROLE_ADMIN',
                ],
            ])
            // Never bound to the entity: the entity holds a hash, the form takes
            // a plaintext password that the controller hashes before saving.
            ->add('password', PasswordType::class, [
                'label' => 'Passwort',
                'mapped' => false,
                'required' => $options['require_password'],
                'help' => $options['require_password']
                    ? null
                    : 'Leer lassen, um das bestehende Passwort beizubehalten.',
                'constraints' => $options['require_password']
                    ? [new NotBlank(message: 'Bitte ein Passwort vergeben.'), new Length(min: 8, minMessage: 'Mindestens {{ limit }} Zeichen.')]
                    : [new Length(min: 8, minMessage: 'Mindestens {{ limit }} Zeichen.')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => true,
        ]);

        $resolver->setAllowedTypes('require_password', 'bool');
    }
}
