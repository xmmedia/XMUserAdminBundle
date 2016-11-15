<?php

namespace XM\UserAdminBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\User;
use AppBundle\Form\Type\UserFormType;

/**
 * Login History controller.
 *
 * @Route("/admin/login-history")
 */
class AdminLoginHistoryController extends Controller
{
    /**
     * Displays the login history all attempts or for a specific user.
     *
     * @Route("{id}", name="xm_user_admin_login_history", defaults={"id": null})
     * @Method("GET")
     */
    public function listAction(Request $request, User $user = null)
    {
        if (null !== $user) {
            $authLogs = $user->getAuthLogs();
            $view = 'list_user';
        } else {
            $authLogs = $this->getDoctrine()
                ->getRepository('AppBundle:AuthLog')
                ->findBy([], ['datetime' => 'DESC']);
            $view = 'list';
        }

        $pagination = $this->get('xm_user_admin.paginator')->paginate(
            $authLogs,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render(
            'XMUserAdminBundle:AdminLoginHistory:'.$view.'.html.twig',
            [
                'user'      => $user,
                'auth_logs' => $pagination,
            ]
        );
    }
}