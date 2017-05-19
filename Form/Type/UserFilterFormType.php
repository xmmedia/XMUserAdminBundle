<?php

namespace XM\UserAdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use XM\FilterBundle\Form\Type\BooleanType;
use XM\FilterBundle\Form\Type\FilterFormType;

class UserFilterFormType extends FilterFormType
{
    /**
     * {@inheritdoc}
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
                'choices' => $this->getUserTypeChoices($options['user_role_choices']),
            ])
            ->add('only_active', BooleanType::class, [
                'label' => 'Only Active Users',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'user_role_choices' => [],
        ]);
    }

    /**
     * Creates array user type choices.
     * Additional choices will be added under a "Role / Permission" optgroup.
     *
     * @param array $additionalChoices
     * @return array
     */
    protected function getUserTypeChoices(array $userRoles = [])
    {
        $choices = [
            'Any User Type'          => 'all',
            'Exclude Administrators' => 'non_admin',
            'Only Administrators'    => 'admin_only',
        ];
        if (!empty($userRoles)) {
            $choices['Role / Permission'] = $userRoles;
        }

        return $choices;
    }
}
