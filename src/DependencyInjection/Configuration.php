<?php

namespace Pada\RequestBodyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('request_body');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('controller')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('dir')->defaultValue('%kernel.project_dir%/src')->end()
                    ->end()
                ->end() // controller
            ->end()
        ;

        return $treeBuilder;
    }
}
