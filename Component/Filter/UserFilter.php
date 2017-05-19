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
     * @var array
     */
    protected $adminRoles = [];

    /**
     * {@inheritdoc}
     */
    public function filterDefaults()
    {
        return [
            'text' => null,
            'user_type' => 'all',
            // 1 = checked
            'only_active' => 1,
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
            // admin only or non-admin option
            if (in_array($filters['user_type'], ['admin_only', 'non_admin'])) {
                if ($filters['user_type'] == 'admin_only') {
                    $operator = 'LIKE';
                    $expr = $qb->expr()->orX();
                } else if ($filters['user_type'] == 'non_admin') {
                    $operator = 'NOT LIKE';
                    $expr = $qb->expr()->andX();
                }

                $i = 0;
                foreach ($this->adminRoles as $role) {
                    $expr->add('u.roles '.$operator.' :role'.$i);
                    $qb->setParameter('role'.$i, '%"'.$role.'"%');
                    ++$i;
                }

                $qb->andWhere($expr);


            } else {
                // a specific role
                $qb->andWhere('u.roles LIKE :role')
                    ->setParameter('role', '%"'.$filters['user_type'].'"%');
            }
        }

        return $qb;
    }

    /**
     * Set the admin roles used for filtering on admin & non-admin users.
     *
     * @param array $roles
     */
    public function setAdminRoles(array $roles = [])
    {
        $this->adminRoles = $roles;
    }
}