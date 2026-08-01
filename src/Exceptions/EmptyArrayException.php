<?php

namespace DRC\ConfigResolver\Exceptions;

class EmptyArrayException extends ConfigException
{
    public function __construct(string $key)
    {
        parent::__construct(
            "Configuration key [{$key}] must not be empty."
        );
    }
}
