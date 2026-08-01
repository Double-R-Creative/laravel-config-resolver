<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidArrayException extends ConfigException
{
    public function __construct(string $key, mixed $value)
    {
        parent::__construct(
            "Configuration key [{$key}] must resolve to an array, [".self::describeValue($value).'] given.'
        );
    }
}
