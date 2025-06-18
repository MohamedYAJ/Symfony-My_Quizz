<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateQuizForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quizName', TextType::class, ['label' => 'Quiz Name'])
            ->add('question', TextType::class, ['label' => 'Question'])
            ->add('option1', TextType::class, ['label' => 'Option 1'])
            ->add('option2', TextType::class, ['label' => 'Option 2'])
            ->add('option3', TextType::class, ['label' => 'Option 3'])
            ->add('correctOption', TextType::class, ['label' => 'Correct Option (1, 2, or 3)']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // No entity binding, plain form
        $resolver->setDefaults([]);
    }
}
