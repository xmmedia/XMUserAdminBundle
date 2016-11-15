<?php

namespace XM\UserAdminBundle\Component\Filter;

use XM\FilterBundle\Component\FilterComponent;
use XM\UserAdminBundle\Form\Type\UserFilterFormType;

class UserFilter extends FilterComponent
{
    /**
     * {@inheritdoc}
     */
    protected $sessionKey = 'user_list';

    /**
     * {@inheritdoc}
     */
    protected $formType = UserFilterFormType::class;

    /**
     * {@inheritdoc}
     */
    public function filterDefaults()
    {
        return [
            'text' => null,
            'user_type' => 'all',
            'only_active' => true,
        ];
    }

    /**
     * Creates the query builder for the user list.
     *
     * @param  \Doctrine\ORM\EntityRepository $repo The user repo.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function createQuery($repo)
    {
        $filters = $this->getFormData();
        $qb = $repo->createQueryBuilder('u')
            ->andWhere('u.expired = false')
            ->orderBy('u.email', 'ASC')
        ;

        if ($filters['only_active']) {
            $qb->andWhere('u.enabled = true')
                ->andWhere('u.locked = false');
        }
        if (!empty($filters['text'])) {
            $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('u.email', ':text'),
                    $qb->expr()->like('u.firstName', ':text'),
                    $qb->expr()->like('u.lastName', ':text')
                ))
                ->setParameter('text', '%' . $filters['text'] . '%')
            ;
        }
        if ($filters['user_type'] != 'all') {
            $userAdminRole = 'ROLE_SUPER_ADMIN';

            if ($filters['user_type'] == 'admin_only') {
                $operator = 'LIKE';
            } else {
                $operator = 'NOT LIKE';
            }
            $qb->andWhere('u.roles '.$operator.' :role')
                // parameter includes double quotes so we don't accidentally get a different role
                // such as ADMIN retrieving ROLE_ADMIN and ROLE_SUPER_ADMIN
                ->setParameter('role', '%"' . $userAdminRole . '"%')
            ;
        }

        return $qb;
    }
}