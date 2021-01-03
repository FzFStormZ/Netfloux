<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // To set up countries in ChoiceType
        foreach($options['countries'] as $country)
        {
            $countries[$country->getName()] = $country;
        }

        // To set up genres in ChoiceType
        foreach($options['genres'] as $genre)
        {
            $genres[$genre->getName()] = $genre;
        }

        $builder
            ->add('title', TextType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('country', ChoiceType::class, [
                'mapped' => false,
                'placeholder' => "Choose a country",
                'required' => false,
                'choices' => $countries,
            ])
            ->add('genre', ChoiceType::class, [
                'mapped' => false,
                'placeholder' => "Choose a genre",
                'required' => false,
                'choices' => $genres,
            ])
            ->add('sort', ChoiceType::class, [
                'mapped' => false,
                'placeholder' => "Choose a sort",
                'required' => false,
                'choices' => [
                    'Ascending' => 'ASC',
                    'Descending' => 'DES'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'countries' => array(),
            'genres' => array(),
        ]);

        $resolver->setRequired('countries');
        $resolver->setAllowedTypes('countries', ["array"]);

        $resolver->setRequired('genres');
        $resolver->setAllowedTypes('genres', ["array"]);
    }
}
