<?php

namespace XM\UserAdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
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
            ->add('text', null, [
                'label' => 'Search',
                'attr' => ['maxlength' => 150],
            ])
            ->add('user_type', ChoiceType::class, [
                'label' => 'User Type',
                'choices' => [
                    'all' => 'All Users',
                    'non_admin' => 'Exclude Administrators',
                    'admin_only' => 'Only Administrators',
                ],
            ])
            ->add('only_active', CheckboxType::class, [
                'label' => 'Only Active Users',
            ])
        ;
    }
}
