# Laasti/Lazydata

Provides lazy loading of data to views. Dot notation can be used.

## Installation

```
composer require laasti/lazydata
```

## Usage

All PHP callables are supported. To pass arguments to calls, use an array like ['my_callable', [/* args here */]].

Without League\Container:

```php

$data = [
    'title' => 'render_title',
    'with_arguments' => ['=my_callable', [/* args here */]],
    'with_class' => ['=my_class', 'my_function'], //or '=my_class::my_function',
    'with_object' => [$object, 'my_function'],
    'meta' => function() {
        return [
            'description' => 'My description'
        ]
    }
];

$viewdata = new Laasti\Lazydata\Data($data);
$viewdata->set('username', function() {return 'George';});

//You can use dot notation within the lazy loaded data
$viewdata->get('meta.description'); //Returns 'My description'

```

Using filters, you can define your own filter with `setFilter` or use native PHP functions that take one string argument.

```php
    $data = [
        'native_example' => 'strtoupper:test', //I know, it's a stupid example :P
        'closure_example' => 'closure:Test',
    ];
    $resolver = new \Laasti\Lazydata\FilterResolver;
    $resolver->setFilter('closure', function($value) {
        return md5($value.'MYSALT');
    });
    $viewdata = new Laasti\Lazydata\Data($data, $resolver);

    $viewdata->get('native_example'); //Returns 'TEST'
    $viewdata->get('closure_example'); //Returns '56e29f03228697ad59822c71eb4d7750'

```

With league/container:

```php

//We need to setup the ContainerResolver that comes with the package
$container = new League\Container\Container;
$container->add('Laasti\Lazydata\ResolverInterface', 'Laasti\Lazydata\ContainerResolver')->withArgument($container);
$container->add('Laasti\Lazydata\Data')->withArguments([[], 'Laasti\Lazydata\ResolverInterface']);

$viewdata = $container->get('Laasti\Lazydata\Data);;

$container->add('container_key', 'some value');

$viewdata->set('viewdata_key', '=container_key');
$viewdata->get('viewdata_key'); //Returns 'some value'

//Returns the value from SomeClass->myMethod();, SomeClass is resolved with the container
$viewdata->set('viewdata_callable_key', '=SomeClass::myMethod');
$viewdata->get('viewdata_callable_key');

//Returns the value from SomeClass->myMethod('George'); SomeClass is resolved with the container
$viewdata->set('viewdata_callable_args_key', ['=SomeClass::myMethod', ['George']]);
$viewdata->get('viewdata_callable_args_key');

```

The ContainerResolver falls back on the default resolver if it cannot resolve the call.

> **Note:**
> Does not work with league/container invokables. It is a limitation due to the way registered callables are stored,
> there is no way to check if a callable is registered to the container in the public API.

## Contributing

1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D

## History

See CHANGELOG.md for more information.

## Credits

Author: Sonia Marquette (@nebulousGirl)

## License

Released under the MIT License. See LICENSE.txt file.




