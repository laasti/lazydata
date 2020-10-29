<?php


namespace Laasti\Lazydata\Tests;

use CallableObject;
use InvokableObject;
use Laasti\Lazydata\Data;
use Laasti\Lazydata\IterableData;
use Laasti\Lazydata\Resolvers\CallableResolver;
use Laasti\Lazydata\Resolvers\ContainerResolver;
use Laasti\Lazydata\Resolvers\FilterResolver;
use Laasti\Lazydata\ResponderData;
use League\Container\Container;
/**
 * DataTest Class
 *
 */
class DataTest extends \PHPUnit_Framework_TestCase
{

    public function testStraightValues()
    {
        $data = [
            'string' => 'MyTest', 'number' => 0, 'boolean' => false, 'array' => ['key', 'key2'],
            'object' => (object) ['property' => 'testProperty']
        ];
        $viewdata = new Data($data);

        $this->assertTrue($viewdata->get('string') === 'MyTest');
        $this->assertTrue($viewdata->get('number') === 0);
        $this->assertTrue($viewdata->get('boolean') === false);
        //TODO what of protected keywords
        $this->assertTrue($viewdata->get('array')->toArray() === ['key', 'key2']);
        $this->assertTrue($viewdata->get('object')->property === 'testProperty');
        $this->assertTrue($viewdata->get('notfound') === null);
    }

    public function testCallableResolver()
    {
        require 'dummy-callables.php';
        $obj = new CallableObject;
        $inv = new InvokableObject;
        $data = [
            'function' => '=functionCallable',
            'function_array' => ['=functionCallable'],
            'function_param' => ['=functionCallable', ['my name']],
            'static_short' => '=StaticCallable::get',
            'static_short_array' => ['=StaticCallable::get'],
            'static_short_param' => ['=StaticCallable::get', ['my name']],
            'static' => ['=StaticCallable', 'get', ['my name']],
            'object' => [$obj, 'get'],
            'object_direct' => $obj,
            'object_param' => [$obj, 'get', ['my name']],
            'object_param_required' => [$obj, 'getParam'],
            'invokable' => [$inv, ['my name']],
            'closure' => function() {
        return 'Closure';
    },
            'dot' => [
                'notation' => [
                    'closure' => function() {
                        return 'notation';
                    },
                    'closureValue' => function() {
                        return ['value' => 'notationvalue'];
                    }
                        ]
                    ]
                ];
                $viewdata = new Data($data);

                $this->assertTrue($viewdata->get('function') === 'function default');
                $this->assertTrue($viewdata->get('function_array') === 'function default');
                $this->assertTrue($viewdata->get('function_param') === 'function my name');
                $this->assertTrue($viewdata->get('static_short') === 'static default');
                $this->assertTrue($viewdata->get('static_short_array') === 'static default');
                $this->assertTrue($viewdata->get('static_short_param') === 'static my name');
                $this->assertTrue($viewdata->get('static') === 'static my name');
                $this->assertTrue($viewdata->get('object') === 'object default');
                $this->assertTrue($viewdata->get('object_direct') === $obj);
                $this->assertTrue($viewdata->get('object_param') === 'object my name');
                $this->assertTrue($viewdata->get('invokable') === 'invoke my name');
                $this->assertTrue($viewdata->get('closure') === 'Closure');
                $this->assertTrue($viewdata->get('dot.notation.closure') === 'notation');
                $this->assertTrue($viewdata->get('dot.notation.closureValue.value') === 'notationvalue');
            }
/**/
            public function testContainerResolver()
            {
                require_once 'dummy-callables.php';
                $container = new Container;
                $container->add('config_string', 'My config');
                $container->add('config_array', ['config' => 'oh my']);
                $container->add('InvokableObject');
                $container->add('my_callable', function() {
                    return 'invoke callable';
                });
                $container->add('some_class', 'CallableObject', true);
                $provider = $this->createMock('League\Container\ServiceProvider\AbstractServiceProvider');
                $provider->expects($this->any())->method('provides')->will($this->returnCallback(function($alias) {
                            return empty($alias) ? ['provided_class'] : $alias === 'provided_class';
                        }));
                $provider->expects($this->once())->method('register')->will($this->returnCallback(function() use ($container) {
                            $container->add('provided_class', 'CallableObject');
                        }));
                $container->addServiceProvider($provider);
                $data = [
                    0 => 'test',
                    'string' => '=config_string',
                    'array' => '=config_array',
                    'callable' => '=my_callable',
                    'object' => '=some_class::get',
                    'object_direct' => '=some_class',
                    'object_array' => ['=some_class', 'get'],
                    'object_array2' => ['=some_class::get'],
                    'object_params' => ['=some_class::get', ['name']],
                    'object_params_array' => ['=some_class', 'get', ['name']],
                    'provided' => '=provided_class::get',
                    'provided_array' => ['=provided_class', 'get'],
                    'provided_params' => ['=provided_class::get', ['name']],
                    'invokable' => ['=InvokableObject', ['name']],
                    'provided_params_array' => ['=provided_class', 'get', ['name']],
                ];
                $viewdata = new Data($data, new ContainerResolver($container));

                $this->assertTrue($viewdata->get(0) === 'test');
                $this->assertTrue($viewdata->get('string') === 'My config');
                $this->assertTrue($viewdata->get('array.config') === 'oh my');
                //$this->assertTrue($viewdata->get('callable') === 'invoke callable');
                $this->assertTrue($viewdata->get('object') === 'object default');
                $this->assertTrue($viewdata->get('object_direct') === $container->get('some_class'));
                $this->assertTrue($viewdata->get('object_array') === 'object default');
                $this->assertTrue($viewdata->get('object_array2') === 'object default');
                $this->assertTrue($viewdata->get('object_params') === 'object name');
                $this->assertTrue($viewdata->get('object_params_array') === 'object name');
                $this->assertTrue($viewdata->get('provided') === 'object default');
                $this->assertTrue($viewdata->get('provided_array') === 'object default');
                $this->assertTrue($viewdata->get('provided_params') === 'object name');
                $this->assertTrue($viewdata->get('provided_params_array') === 'object name');
                //No automatic reflexion in container2
                //$this->assertTrue($viewdata->get('reflection') === 'object default');
                $this->assertTrue($viewdata->get('invokable') === 'invoke name');
            }

            public function testFilterResolver()
            {
                $data = [
                    'native' => 'strtoupper:test',
                    'closure' => 'closure:Test',
                    'unset'  => 'unset:Test'
                ];
                $resolver = new FilterResolver;
                $resolver->setFilter('closure', function() {
                    return 'My closure';
                });
                $resolver->setFilter('unset', function() {
                    return 'My closure2';
                });
                $resolver->removeFilter('unset');
                $viewdata = new Data($data, $resolver);
                $this->assertTrue($viewdata->get('native') === 'TEST');
                $this->assertTrue($viewdata->get('closure') === 'My closure');
                $this->assertTrue($viewdata->get('unset') === 'unset:Test');
            }

            public function testInvalidKey()
            {
                $this->setExpectedException('InvalidArgumentException');
                $resolver = new FilterResolver;
                $resolver->setFilter('bcu&62-', function() {
                    return 'My closure';
                });
            }

            public function testInvalidCallable()
            {
                $this->setExpectedException('InvalidArgumentException');
                $resolver = new FilterResolver;
                $resolver->setFilter('mycallable', 'some_invalid_function');
            }

            public function testProvider()
            {
                $container = new Container;
                $container->share('Interop\Container\ContainerInterface', $container);
                $container->addServiceProvider('Laasti\Lazydata\Providers\LeagueLazydataProvider');

                $this->assertTrue($container->get('Laasti\Lazydata\Resolvers\FilterResolver') instanceof FilterResolver);
                $this->assertTrue($container->get('Laasti\Lazydata\Resolvers\ContainerResolver') instanceof ContainerResolver);
                $this->assertTrue($container->get('Laasti\Lazydata\Resolvers\CallableResolver') instanceof CallableResolver);
                //$this->assertTrue($container->get('Laasti\Lazydata\ResponderData') instanceof ResponderData);
                $this->assertTrue($container->get('Laasti\Lazydata\Data') instanceof Data);
                $this->assertTrue($container->get('Laasti\Lazydata\IterableData') instanceof IterableData);
                $this->assertTrue($container->get('Laasti\Lazydata\Resolvers\ResolverInterface') instanceof ContainerResolver);
            }

            public function testFilterResolverProvider()
            {
                require_once 'dummy-callables.php';
                $container = new Container;
                $container->add('config', ['lazydata' => ['filters' => ['data' => '=StaticCallable::get']]]);
                $container->add('Interop\Container\ContainerInterface', $container);
                $container->addServiceProvider('Laasti\Lazydata\Providers\LeagueLazydataProvider');

                $this->assertTrue($container->get('Laasti\Lazydata\Resolvers\ResolverInterface') instanceof FilterResolver);
            }

            public function testCallableResolverProvider()
            {
                require_once 'dummy-callables.php';
                $container = new Container;
                $container->add('config', ['lazydata' => ['container' => false]]);
                $container->add('Interop\Container\ContainerInterface', $container);
                $container->addServiceProvider('Laasti\Lazydata\Providers\LeagueLazydataProvider');

                $this->assertTrue($container->get('Laasti\Lazydata\Resolvers\ResolverInterface') instanceof CallableResolver);
            }

            public function testSaveResolvableData()
            {
                $viewdata = new Data(['menu' => function() {return new \stdClass();}, 'menus.footer' => function() {return new \stdClass();}]);

                $this->assertTrue($viewdata->get('menu') === $viewdata->get('menu'));
                $this->assertTrue($viewdata->get('menus.footer') === $viewdata->get('menus.footer'));
                $this->assertTrue($viewdata->get('menus.footer') === $viewdata->get('menus')->get('footer'));
            }
/**/
        }
