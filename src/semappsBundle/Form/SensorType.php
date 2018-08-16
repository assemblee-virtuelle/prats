<?php

namespace semappsBundle\Form;

use semappsBundle\semappsConfig;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use VirtualAssembly\SemanticFormsBundle\Form\SemanticFormType;
use VirtualAssembly\SemanticFormsBundle\Form\UriType;
use VirtualAssembly\SemanticFormsBundle\SemanticFormsBundle;

class SensorType extends SemanticFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // This will manage form specification.
        parent::buildForm($builder, $options);

        $this->add($builder, 'preferedLabel', TextType::class)
        ->add($builder, 'alternativeLabel', TextType::class, ['required' => false,])
        ->add(
            $builder,
            'documents',
            UriType::class,
            [
                'required'  => false,
                'rdfType'   => semappsConfig::URI_PAIR_PERSON."|".semappsConfig::URI_PAIR_PROJECT,
            ]
            );
        $builder->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }
}