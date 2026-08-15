<?php

namespace App\Form;

use App\Entity\Offer;
use App\Entity\TaskDraw;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskDrawType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('base64Data', TextType::class, [
                'required' => false,
                'label' => false,
                'attr' => [
                    'class' => 'task_draw_base64',
                ]
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskDraw::class,
        ]);
    }
}
