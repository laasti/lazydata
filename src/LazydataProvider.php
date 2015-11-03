<?php

namespace Laasti\Lazydata;

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

        $data = $di->get('config.lazydata.data');
        $container = $di->get($di->get('config.lazydata.container'));

        $resolvers = [];

        $di->add('Laasti\Lazydata\Resolvers\FilterResolver', function($filters, $separator) use ($di) {
            $resolver = new Resolvers\FilterResolver($separator ?: ':');
            foreach ($filters as $key => $callable) {
                $resolver->setFilter($key, $callable);
            }
            return $resolver;
        })->withArguments(['config.lazydata.filters', 'config.lazydata.filter_separator']);

        $di->add('Laasti\Lazydata\Resolvers\ContainerResolver')->withArgument('config.lazydata.container');
        $di->add('Laasti\Lazydata\Resolvers\CallableResolver');

        $di->add('Laasti\Lazydata\Resolvers\ResolverInterface');

        $di->add('Laasti\Lazydata\ResponderData')->withArguments([$data, 'Laasti\Lazydata\Resolvers\ResolverInterface']);
        $di->add('Laasti\Lazydata\Data')->withArguments([$data, 'Laasti\Lazydata\Resolvers\ResolverInterface']);
        $di->add('Laasti\Lazydata\IterableData')->withArguments([$data, 'Laasti\Lazydata\Resolvers\ResolverInterface']);

    }
}
