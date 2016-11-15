<?php

namespace Laasti\Lazydata\Providers;

use Laasti\Lazydata\Resolvers\CallableResolver;
use Laasti\Lazydata\Resolvers\ContainerResolver;
use Laasti\Lazydata\Resolvers\FilterResolver;
use League\Container\ContainerInterface;
use League\Container\ServiceProvider;

class LeagueLazydataProvider extends ServiceProvider\AbstractServiceProvider
{
    protected $provides = [
        'Laasti\Lazydata\Resolvers\ResolverInterface',
        'Laasti\Lazydata\Resolvers\CallableResolver',
        'Laasti\Lazydata\Resolvers\ContainerResolver',
        'Laasti\Lazydata\Resolvers\FilterResolver',
        'Laasti\Lazydata\Data',
        'Laasti\Lazydata\IterableData',
        'lazydata.data',
        'lazydata.container',
        'lazydata.filters',
        'lazydata.filters_separator',
        'lazydata.prefix',
    ];
    
    protected $defaultConfig = [
        'data' => [],
        'container' => 'Interop\Container\ContainerInterface',
        'filters' => [],
        'filter_separator' => ':',
        'prefix' => '=',
    ];

    public function register()
    {
       
        $di = $this->getContainer();
        $config = $this->getConfig();
        $di->add('lazydata.data', $config['data']);
        if ($config['container']) {
            $di->add('lazydata.container', $di->get($config['container']));
        } else {
            $di->add('lazydata.container', false);
        }
        $di->add('lazydata.filters', $config['filters']);
        $di->add('lazydata.filter_separator', $config['filter_separator']);
        $di->add('lazydata.prefix', $config['prefix']);

        $di->add('Laasti\Lazydata\Resolvers\FilterResolver', function($filters, $separator, $fallback = null) {
            $fallback = $fallback ?: new CallableResolver;
            $resolver = new FilterResolver($separator ?: ':', $fallback);
            foreach ($filters as $key => $callable) {
                $resolver->setFilter($key, $callable);
            }
            return $resolver;
        })->withArguments(['lazydata.filters', 'lazydata.filter_separator']);

        $di->add('Laasti\Lazydata\Resolvers\ContainerResolver')->withArguments(['lazydata.container', 'Laasti\Lazydata\Resolvers\CallableResolver', 'lazydata.prefix']);
        $di->add('Laasti\Lazydata\Resolvers\CallableResolver')->withArgument('lazydata.prefix');
        $di->add('Laasti\Lazydata\Data')->withArguments(['lazydata.data', 'Laasti\Lazydata\Resolvers\ResolverInterface']);
        $di->add('Laasti\Lazydata\IterableData')->withArguments(['lazydata.data', 'Laasti\Lazydata\Resolvers\ResolverInterface']);

        $di->add('Laasti\Lazydata\Resolvers\ResolverInterface', function($container, $prefix, $filters, $separator) use ($di) {
            $callable = new CallableResolver($prefix);
            if (is_string($container)) {
                $container = $di->get($container);
            }
            if ($container instanceof ContainerInterface && count($filters)) {
                $containerResolver = new ContainerResolver($container, $callable, $prefix);
                $resolver = new FilterResolver($separator, $containerResolver);
                foreach ($filters as $key => $callable) {
                    if (!is_callable($callable)) {
                        $callable = $containerResolver->getCallable($callable);
                        if (is_array($callable)) {
                            $callable = $callable[0];
                        }
                    }
                    $resolver->setFilter($key, $callable);
                }
            } else if ($container instanceof ContainerInterface) {
                $resolver = new ContainerResolver($container, $callable, $prefix);
            } else if (count($filters)) {
                $resolver = new FilterResolver($separator, $callable);
                foreach ($filters as $key => $callable) {
                    $resolver->setFilter($key, $callable);
                }                
            } else {
                $resolver = $callable;
            }
            return $resolver;
        })->withArguments(['lazydata.container', 'lazydata.prefix', 'lazydata.filters', 'lazydata.filter_separator']);
    }
    
    protected function getConfig()
    {
        if ($this->getContainer()->has('config')) {
            $config = $this->getContainer()->get('config');

            if (isset($config['lazydata'])) {
                return array_merge($this->defaultConfig, $config['lazydata']);
            }
        }
        
        return $this->defaultConfig;
    }
}
