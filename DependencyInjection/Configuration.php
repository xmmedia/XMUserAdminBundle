<?php

namespace XM\UserAdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use XM\UserAdminBundle\Form\Type\UserFormType;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('xm_user_admin');

        $rootNode
            ->children()
                ->arrayNode('forms')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->arrayNode('user_admin')
                            ->addDefaultsIfNotSet()
                            ->canBeUnset()
                            ->children()
                                ->scalarNode('type')
                                    ->defaultValue(UserFormType::class)
                                ->end()
                            ->end()
                        ->end() // user admin
                    ->end()
                ->end() // forms

                ->arrayNode('roles')
                    ->info('The roles that will be available when adding or editing users in the user admin. These will also be used for the User Type admin filter.')
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
                    ->defaultValue($this->getDefaultRoles())
                ->end() // roles

                ->arrayNode('admin_roles')
                    ->info('The roles that are considered admin roles.')
                    ->treatNullLike([])
                    ->prototype('scalar')->end()
                    ->defaultValue($this->getAdminRoles())
                ->end() // admin_roles
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * The default role options in the user admin.
     *
     * @return array
     */
    protected function getDefaultRoles()
    {
        return [
            'Super Admin' => 'ROLE_SUPER_ADMIN',
        ];
    }

    /**
     * The roles that are considered admin roles.
     * Used for searching/filtering.
     *
     * @return array
     */
    protected function getAdminRoles()
    {
        return ['ROLE_SUPER_ADMIN'];
    }
}
