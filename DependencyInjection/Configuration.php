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
            ->end()
        ;

        return $treeBuilder;
    }
}
