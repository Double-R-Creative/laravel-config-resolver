<?php

namespace DRC\ConfigResolver\Exceptions;

class InvalidArrayTypeException extends ConfigException
{
    public function __construct(string $key, string $type)
    {
        parent::__construct(
            "Configuration key [{$key}] must resolve to a [{$type}] array."
        );
    }
}
