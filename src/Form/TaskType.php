<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', null, ['label' => 'Anmerkung', 'required' => false])
            ->add('textarea', TextareaType::class, ['label' => 'Freitext', 'required' => false])
            ->add('taskDate', DateTimeType::class, [
                'label' => 'Angebotsdatum',
                "widget" => 'single_text'
            ])
            ->add('termDate', DateTimeType::class, [
                'label' => 'Termin',
                "widget" => 'single_text',
                'required' => false
            ])
            ->add('customer', null, ['label' => 'Kunde'])
            ->add('taskImages', FileType::class, [
                'mapped' => false,
                'label' => 'Dateien',
                'required' => false,
                'multiple' => true,
                'attr' => ['placeholder' => 'Datei wählen'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
