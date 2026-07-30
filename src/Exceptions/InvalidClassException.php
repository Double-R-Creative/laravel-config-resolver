<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidClassException extends ConfigException
{
    public function __construct(string $key, string $class)
    {
        parent::__construct(
            "Configuration key [{$key}] resolves to [{$class}], but that class does not exist."
        );
    }
}