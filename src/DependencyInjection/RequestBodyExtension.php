<?php


namespace Pada\RequestBodyBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;


class RequestBodyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $cacheWarmer = $container->getDefinition('request_body_cache_warmer');
        $cacheWarmer->replaceArgument(0, $config['controller']['dir']);

        $service = $container->getDefinition('request_body_service');
        $service->addMethodCall('setLogger', [new Reference('logger')]);
    }
}
