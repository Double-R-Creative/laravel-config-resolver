<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidStringException extends ConfigException
{
    public function __construct(string $key, mixed $value)
    {
        parent::__construct(
            "Configuration key [{$key}] must resolve to a string, [".self::describeValue($value).'] given.'
        );
    }
}
