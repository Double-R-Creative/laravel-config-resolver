<?php

namespace DRC\ConfigResolver;

use DRC\ConfigResolver\Resolvers\ClassResolver;

class Config
{
    public static function class(string $key): ClassResolver
    {
        return new ClassResolver($key);
    }
}
