<?php

namespace XM\UserAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class UserFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'attr' => ['maxlength' => 180, 'autofocus' => true],
                'label' => 'Email/Username',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 3, 'max' => 180]),
                    new Assert\Email(),
                ],
            ])
            ->add('firstName', null, [
                'attr' => ['maxlength' => 255],
            ])
            ->add('lastName', null, [
                'attr' => ['maxlength' => 255],
            ])
            ->add('setPassword', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Set Password',
                'required' => false,
            ])
            // this field is re-added below, without the constraints
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'label' => 'Password',
                'required' => false,
                'attr' => ['maxlength' => 4096],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 12, 'max' => 4096]),
                ],
            ])
        ;

        // add a form event listener so the password field is not required
        // if the set password field is *not* checked
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function(FormEvent $event) {
                $form = $event->getForm();
                $setPassword = $form->get('setPassword')->getData();

                if (!$setPassword) {
                    $form->add('password', PasswordType::class, [
                        'mapped' => false,
                        'label' => 'Password',
                        'required' => false,
                        'attr' => ['maxlength' => 4096],
                    ]);
                }
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // @todo is this right? can we just use the security bundle user?
            'data_class' => 'AppBundle\Entity\User'
        ]);
    }
}
