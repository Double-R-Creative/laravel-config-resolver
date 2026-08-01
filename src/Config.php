<?php

namespace DRC\ConfigResolver;

use DRC\ConfigResolver\Resolvers\ArrayResolver;
use DRC\ConfigResolver\Resolvers\ClassResolver;
use DRC\ConfigResolver\Resolvers\StringResolver;

class Config
{
    public static function class(string $key, mixed $default = null): ClassResolver
    {
        return new ClassResolver($key, $default);
    }

    public static function array(string $key, mixed $default = null): ArrayResolver
    {
        return new ArrayResolver($key, $default);
    }

    public static function string(string $key, mixed $default = null): StringResolver
    {
        return new StringResolver($key, $default);
    }
}
