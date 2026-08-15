<?php

namespace App\Form;

use App\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, ['label' => 'Anrede'])
            ->add('firstname', null, ['label' => 'Vorname'])
            ->add('surname', null, ['label' => 'Nachname'])
            ->add('email', null, ['label' => 'E-Mail', 'required' => false])
            ->add('phone', null, ['label' => 'Telefon', 'required' => true])
            ->add('mobilenumber', null, ['label' => 'Mobil', 'required' => false])
            ->add('street', null, ['label' => 'Straße'])
            ->add('housenumber', null, ['label' => 'Hausnummer'])
            ->add('plz', null, ['label' => 'PLZ'])
            ->add('city', null, ['label' => 'Ort'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
