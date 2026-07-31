<?php

namespace DRC\ConfigResolver;

use DRC\ConfigResolver\Resolvers\ClassResolver;

class Config
{
    public static function class(string $key, mixed $fallback = null): ClassResolver
    {
        return new ClassResolver($key, $fallback);
    }
}
