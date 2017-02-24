<?php

namespace XM\UserAdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use XM\FilterBundle\Form\Type\BooleanType;
use XM\FilterBundle\Form\Type\FilterFormType;

class UserFilterFormType extends FilterFormType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', TextType::class, [
                'label' => 'Search',
                'attr' => ['maxlength' => 150],
            ])
            ->add('user_type', ChoiceType::class, [
                'label' => 'User Type',
                'choices' => [
                    'All Users' => 'all',
                    'Exclude Administrators' => 'non_admin',
                    'Only Administrators' => 'admin_only',
                ],
            ])
            ->add('only_active', BooleanType::class, [
                'label' => 'Only Active Users',
            ])
        ;
    }
}
