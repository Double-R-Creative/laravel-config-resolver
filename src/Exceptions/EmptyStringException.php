<?php

namespace DRC\ConfigResolver\Exceptions;

class EmptyStringException extends ConfigException
{
    public function __construct(string $key)
    {
        parent::__construct(
            "Configuration key [{$key}] must not be empty."
        );
    }
}
