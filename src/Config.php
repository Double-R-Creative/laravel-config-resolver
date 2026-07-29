<?php

namespace Vendor\ConfigResolver;

use Vendor\ConfigResolver\Resolvers\ClassResolver;

class Config
{
    public static function class(string $key): ClassResolver
    {
        return new ClassResolver($key);
    }
}
