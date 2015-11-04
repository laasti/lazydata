<?php

namespace Laasti\Lazydata;

use Laasti\Lazydata\Resolvers\CallableResolver;
use Laasti\Lazydata\Resolvers\ContainerResolver;
use Laasti\Lazydata\Resolvers\FilterResolver;
use League\Container\ContainerInterface;
use League\Container\ServiceProvider;

class LazyDataProvider extends ServiceProvider
{
    protected $provides = [
        'Laasti\Lazydata\Resolvers\ResolverInterface',
        'Laasti\Lazydata\Resolvers\CallableResolver',
        'Laasti\Lazydata\Resolvers\ContainerResolver',
        'Laasti\Lazydata\Resolvers\FilterResolver',
        'Laasti\Lazydata\ResponderData',
        'Laasti\Lazydata\Data',
        'Laasti\Lazydata\IterableData',
    ];

    public function register()
    {
        $di = $this->getContainer();

        if (!$di->isSingleton('config.lazydata.data') && !$di->isRegistered('config.lazydata.data')) {
            $di->add('config.lazydata.data', []);
        }
        if (!$di->isSingleton('config.lazydata.container') && !$di->isRegistered('config.lazydata.container')) {
            $di->add('config.lazydata.container', function() use ($di) {
                return $di->get('League\Container\ContainerInterface');
            });
        }
        if (!$di->isSingleton('config.lazydata.filters') && !$di->isRegistered('config.lazydata.filters')) {
            $di->add('config.lazydata.filters', []);
        }
        if (!$di->isSingleton('config.lazydata.filter_separator') && !$di->isRegistered('config.lazydata.filter_separator')) {
            $di->add('config.lazydata.filter_separator', ':');
        }
        if (!$di->isSingleton('config.lazydata.prefix') && !$di->isRegistered('config.lazydata.prefix')) {
            $di->add('config.lazydata.prefix', '=');
        }

        $di->add('Laasti\Lazydata\Resolvers\FilterResolver', function($filters, $separator, $fallback = null) {
            $fallback = $fallback ?: new CallableResolver;
            $resolver = new FilterResolver($separator ?: ':', $fallback);
            foreach ($filters as $key => $callable) {
                $resolver->setFilter($key, $callable);
            }
            return $resolver;
        })->withArguments(['config.lazydata.filters', 'config.lazydata.filter_separator']);

        $di->add('Laasti\Lazydata\Resolvers\ContainerResolver')->withArguments(['config.lazydata.container', 'Laasti\Lazydata\Resolvers\CallableResolver', 'config.lazydata.prefix']);
        $di->add('Laasti\Lazydata\Resolvers\CallableResolver')->withArgument('config.lazydata.prefix');
        $di->add('Laasti\Lazydata\ResponderData')->withArguments(['config.lazydata.data', 'Laasti\Lazydata\Resolvers\ResolverInterface']);
        $di->add('Laasti\Lazydata\Data')->withArguments(['config.lazydata.data', 'Laasti\Lazydata\Resolvers\ResolverInterface']);
        $di->add('Laasti\Lazydata\IterableData')->withArguments(['config.lazydata.data', 'Laasti\Lazydata\Resolvers\ResolverInterface']);

        $di->add('Laasti\Lazydata\Resolvers\ResolverInterface', function($container, $prefix, $filters, $separator) {
            $callable = new CallableResolver($prefix);
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
        })->withArguments(['config.lazydata.container', 'config.lazydata.prefix', 'config.lazydata.filters', 'config.lazydata.filter_separator']);
    }
}
