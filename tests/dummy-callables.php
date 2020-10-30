<?php

function functionCallable($name = 'default')
{
    return 'function '.$name;
}

class StaticCallable
{

    public static function get($name = 'default')
    {
        return 'static '.$name;
    }

    public static function getArray()
    {
        return [0,1];
    }

}

class CallableObject
{

    public function get($name = 'default')
    {
        return 'object '.$name;
    }

    public function getParam($name)
    {
        return 'objectParam '.$name;
    }

}

class InvokableObject
{

    public function __invoke($name)
    {
        return 'invoke '.$name;
    }

}
