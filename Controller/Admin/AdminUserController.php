<?php

namespace XM\UserAdminBundle\Controller\Admin;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\User;

/**
 * User controller.
 *
 * @Route("/admin/user")
 */
class AdminUserController extends Controller
{
    /**
     * Lists the users
     *
     * @Route("/list", name="xm_user_admin_user_list")
     * @Method("GET")
     */
    public function listAction()
    {
        $repo = $this->getDoctrine()
            ->getRepository('AppBundle:User');

        $userFilter = $this->get('xm_user_admin.filter.user');
        $form = $userFilter->createForm([
            'user_role_choices' => $this->getParameter('xm_user_admin.roles'),
        ]);
        $userFilter->updateSession();

        $qb = $userFilter->createQuery($repo);

        $query = $qb->getQuery();
        $pagination = $userFilter->getPagination($query);

        return $this->render('XMUserAdminBundle:AdminUser:list.html.twig', [
            'pagination' => $pagination,
            'user_filter_form' => $form->createView(),
        ]);
    }

    /**
     * Creates a new User entity.
     *
     * @Route("/new", name="xm_user_admin_user_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->createUser();

        $form = $this->createUserForm($user, [
            'action' => $this->generateUrl('xm_user_admin_user_new'),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $passwordIsSetByAdmin = $form->get('setPassword')->getData();

            if (!$passwordIsSetByAdmin) {
                $tokenGenerator = $this->get('fos_user.util.token_generator');

                // set the password to something crazy, that no one will know
                $user->setPlainPassword($tokenGenerator->generateToken());

                $user->setConfirmationToken($tokenGenerator->generateToken());
                $user->setPasswordRequestedAt(new \DateTime());
            } else {
                $password = $form->get('password')->getData();
                $user->setPlainPassword($password);
                // only enabled accounts that have a proper password
                $user->setEnabled(true);
            }

            $userManager->updateUser($user);

            $this->sendWelcomeEmail($user, $passwordIsSetByAdmin, $request->getSchemeAndHttpHost());

            $msg_key = $passwordIsSetByAdmin ? 'created_set_password' : 'created';
            $msg = $this->get('translator')
                ->trans(
                    'xm_user_admin.message.user.'.$msg_key,
                    [],
                    'XMUserAdminBundle'
                );
            $this->addFlash('success', $msg);

            return $this->redirectToList();

        } else if ($form->isSubmitted()) {
            $msg = $this->get('translator')
                ->trans(
                    'xm_user_admin.message.validation_errors_continue',
                    [],
                    'XMUserAdminBundle'
                );
            $this->addFlash('warning', $msg);
        }

        return $this->render('XMUserAdminBundle:AdminUser:create.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Sends welcome email.
     * Depending if the admin set the password or not will determine which template is used.
     *
     * @param User    $user The user entity.
     * @param boolean $passwordIsSetByAdmin TRUE if the admin set the user's password.
     * @param string  $schemeAndHttpHost The scheme and host (generated path is appended).
     * @return bool
     */
    protected function sendWelcomeEmail(User $user, $passwordIsSetByAdmin, $schemeAndHttpHost)
    {
        if (!$passwordIsSetByAdmin) {
            // send a link to the password reset page (from the forgot password function)
            $view = 'welcome';
            $path = $this->generateUrl('fos_user_resetting_reset', [
                'token' => $user->getConfirmationToken(),
            ]);
        } else {
            // send an email that says they should have received the password from an admin
            $view = 'welcome_set_password';
            $path = $this->generateUrl('fos_user_security_login');
        }

        $template = '@XMUserAdmin/Mail/AdminUser/'.$view.'.html.twig';
        $mailParams = [
            'user' => $user,
            'uri' => $schemeAndHttpHost.$path,
        ];

        $result = $this->get('xm_mail_manager.manager')->getSender()
            ->setTemplate($template, $mailParams)
            ->send($user->getEmail());

        return $result;
    }

    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}/edit", name="xm_user_admin_user_edit")
     * @Method({"GET", "PUT"})
     */
    public function updateAction(Request $request, User $user)
    {
        $userManager = $this->get('fos_user.user_manager');

        $editForm = $this->createUserForm($user, [
            'action' => $this->generateUrl('xm_user_admin_user_edit', [
                'id' => $user->getId()
            ]),
            'method' => 'PUT',
        ]);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $setPassword = $editForm->get('setPassword')->getData();
            if ($setPassword) {
                $password = $editForm->get('password')->getData();
                $user->setPlainPassword($password);
            }

            $userManager->updateUser($user);

            $msg = $this->get('translator')->trans(
                'xm_user_admin.message.entity_updated',
                ['%name%' => 'user'],
                'XMUserAdminBundle'
            );
            $this->addFlash('success', $msg);

            return $this->redirectToList();

        } else if ($editForm->isSubmitted()) {
            $msg = $this->get('translator')
                ->trans(
                    'xm_user_admin.message.validation_errors_continue',
                    [],
                    'XMUserAdminBundle'
                );
            $this->addFlash('warning', $msg);
        }

        $resetPasswordForm = $this->createResetPasswordForm($user);
        $lockUnlockForm = $this->createLockUnlockForm($user);
        $activateForm = $this->createActivateForm($user);
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('XMUserAdminBundle:AdminUser:edit.html.twig', [
            'user' => $user,
            'form' => $editForm->createView(),
            'reset_password_form' => $resetPasswordForm->createView(),
            'activate_form'       => $activateForm->createView(),
            'lock_unlock_form'    => $lockUnlockForm->createView(),
            'delete_form'         => $deleteForm->createView(),
        ]);
    }

    /**
     * Sends the user a reset password link.
     *
     * @Route("/{id}/reset-password", name="xm_user_admin_user_reset_password")
     * @Method("POST")
     */
    public function resetPasswordAction(Request $request, User $user)
    {
        $form = $this->createLockUnlockForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $user->setPasswordRequestedAt(new \DateTime());

            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            $this->sendPasswordResetEmail($user, $request->getSchemeAndHttpHost());

            // this is escaped on output in the template
            $this->addFlash('success', 'An email containing a reset password link has been sent to '.$user->getEmail().'.');
        }

        return $this->redirectToList();
    }

    /**
     * Creates a form to reset the users password.
     *
     * @param User $user The user entity.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    protected function createResetPasswordForm(User $user)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('xm_user_admin_user_reset_password', [
                'id' => $user->getId()
            ]))
            ->setMethod('POST')
            ->getForm()
        ;

        return $form;
    }

    /**
     * Sends the user a link to reset their password.
     *
     * @param User $user The user entity.
     * @param string $schemeAndHttpHost The scheme and host (generated path is appended).
     * @return bool
     */
    protected function sendPasswordResetEmail(User $user, $schemeAndHttpHost)
    {
        $path = $this->generateUrl('fos_user_resetting_reset', [
            'token' => $user->getConfirmationToken(),
        ]);

        $template = '@XMUserAdmin/Mail/AdminUser/reset.html.twig';
        $mailParams = [
            'user' => $user,
            'uri' => $schemeAndHttpHost.$path,
        ];

        $result = $this->get('xm_mail_manager.manager')->getSender()
            ->setTemplate($template, $mailParams)
            ->send($user->getEmail());

        return $result;
    }

    /**
     * Enables or disables a User entity.
     *
     * @Route("/{id}/unlock", name="xm_user_admin_user_unlock")
     * @Route("/{id}/lock", name="xm_user_admin_user_lock")
     * @Method("POST")
     */
    public function lockUnlockAction(Request $request, User $user)
    {
        $form = $this->createLockUnlockForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($request->get('_route') == 'xm_user_admin_user_unlock') {
                $user->unlock();
                $msgKey = 'xm_user_admin.message.user.unlocked';
            } else {
                $user->lock();
                $msgKey = 'xm_user_admin.message.user.locked';
            }

            $this->get('fos_user.user_manager')
                ->updateUser($user)
            ;

            $msg = $this->get('translator')
                ->trans($msgKey, [], 'XMUserAdminBundle');
            $this->addFlash('success', $msg);
        }

        return $this->redirectToList();
    }

    /**
     * Creates a form to set the user as locked or unlocked.
     *
     * @param User $user The user entity.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    protected function createLockUnlockForm(User $user)
    {
        if ($user->isLocked()) {
            $route = 'xm_user_admin_user_unlock';
            $buttonLabel = 'Unlock Account';
        } else {
            $route = 'xm_user_admin_user_lock';
            $buttonLabel = 'Lock Account';
        }

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl($route, ['id' => $user->getId()]))
            ->setMethod('POST')
            ->add('button', SubmitType::class, ['label' => $buttonLabel])
            ->getForm()
        ;

        return $form;
    }

    /**
     * Activate a user's account.
     *
     * @Route("/{id}/activate", name="xm_user_admin_user_activate")
     * @Method("POST")
     */
    public function activateAction(Request $request, User $user)
    {
        $form = $this->createLockUnlockForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $user->setEnabled(true);

            $this->get('fos_user.user_manager')
                ->updateUser($user)
            ;

            $msg = $this->get('translator')
                ->trans(
                    'xm_user_admin.message.user.activated',
                    [],
                    'XMUserAdminBundle'
                );
            $this->addFlash('success', $msg);
        }

        return $this->redirectToList();
    }

    /**
     * Creates a form to activate a user's account.
     *
     * @param User $user The user entity.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    protected function createActivateForm(User $user)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('xm_user_admin_user_activate', ['id' => $user->getId()]))
            ->setMethod('POST')
            ->getForm()
        ;

        return $form;
    }

    /**
     * Deletes a User entity.
     *
     * @Route("/{id}", name="xm_user_admin_user_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $userManager = $this->get('fos_user.user_manager');

            $userManager->deleteUser($user);

            $msg = $this->get('translator')->trans(
                'xm_user_admin.message.entity_deleted',
                ['%name%' => 'user'],
                'XMUserAdminBundle'
            );
            $this->addFlash('success', $msg);
        }

        return $this->redirectToList();
    }

    /**
     * Creates a form to delete a User entity by id.
     *
     * @param User $user The user entity.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    protected function createDeleteForm($user)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('xm_user_admin_user_delete', [
                'id' => $user->getId()
            ]))
            ->setMethod('DELETE')
            ->getForm()
        ;

        return $form;
    }

    /**
     * Returns the redirect response to redirect back to the user list
     * with the filter query.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectToList()
    {
        $redirectUrl = $this->generateUrl('xm_user_admin_user_list');
        $redirectUrl .= '?'.$this->get('xm_user_admin.filter.user')->query();

        return $this->redirect($redirectUrl, 301);
    }

    /**
     * Creates the user form instance.
     *
     * @param User   $user
     * @param array $options
     * @return \Symfony\Component\Form\Form
     */
    protected function createUserForm(User $user, $options = [])
    {
        $form = $this->getParameter('xm_user_admin.forms.user_admin');
        $options['roles'] = $this->getParameter('xm_user_admin.roles');

        return $this->createForm($form, $user, $options);
    }
}