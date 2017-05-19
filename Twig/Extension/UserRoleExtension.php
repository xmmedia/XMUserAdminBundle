<?php

namespace XM\UserAdminBundle\Twig\Extension;

class UserRoleExtension extends \Twig_Extension
{
    /**
     * Role names, where the key is the name and value is the role.
     * @var array
     */
    protected $roleNames = [];

    /**
     * UserRoleExtension constructor.
     *
     * @param array $roleNames
     */
    public function __construct(array $roleNames = [])
    {
        $this->roleNames = $roleNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('xm_user_roles', array($this, 'userRoles')),
        );
    }

    /**
     * Creates human readable array of role names.
     *
     * @param array $roleArray
     * @return array
     */
    public function userRoles(array $roleArray)
    {
        $names = [];

        foreach ($roleArray as $role) {
            $name = array_search($role, $this->roleNames);
            if (false !== $name) {
                $names[] = $name;
            }
        }

        return $names;
    }
}