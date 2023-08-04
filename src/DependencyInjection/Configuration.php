<?php

namespace Paysera\Bundle\LockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('paysera_lock');
        $rootNode = method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('paysera_lock')
        ;

        $rootNode
            ->children()
                ->scalarNode('ttw')->defaultValue(5)->end()
                ->scalarNode('ttl')->defaultNull()->end()
                ->scalarNode('redis_client')->isRequired()->cannotBeEmpty()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
