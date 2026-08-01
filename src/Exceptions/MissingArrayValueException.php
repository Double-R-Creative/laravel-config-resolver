<?php

namespace DRC\ConfigResolver\Exceptions;

class MissingArrayValueException extends ConfigException
{
    public function __construct(string $key, mixed $requiredValue)
    {
        parent::__construct(
            "Configuration key [{$key}] must contain value [".self::describeValue($requiredValue).'].'
        );
    }
}
